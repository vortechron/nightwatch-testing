#!/bin/bash

# Script to fill ClickHouse with test data for Nightwatch storage restriction testing
# Target: At least 20MB of data

set -e

# Configuration
ITERATIONS=${1:-100}  # Number of iterations, default 100
DELAY=${2:-1}         # Delay between iterations in seconds, default 1

echo "=== Nightwatch Data Fill Script ==="
echo "Iterations: $ITERATIONS"
echo "Delay between iterations: ${DELAY}s"
echo ""

# Track progress
count=0
start_time=$(date +%s)

for i in $(seq 1 $ITERATIONS); do
    echo "--- Iteration $i of $ITERATIONS ---"

    # Run nightwatch:test to generate test data
    echo "[$(date +%H:%M:%S)] Running nightwatch:test..."
    php artisan nightwatch:test 2>/dev/null || true

    # Run scheduled tasks
    echo "[$(date +%H:%M:%S)] Running schedule:run..."
    php artisan schedule:run 2>/dev/null || true

    # Process queue jobs (run for a limited time to avoid blocking)
    echo "[$(date +%H:%M:%S)] Processing queue jobs..."
    timeout 10 php artisan queue:work --once 2>/dev/null || true

    count=$((count + 1))

    # Show progress every 10 iterations
    if [ $((i % 10)) -eq 0 ]; then
        elapsed=$(($(date +%s) - start_time))
        echo ""
        echo "Progress: $i/$ITERATIONS iterations completed (${elapsed}s elapsed)"
        echo ""
    fi

    # Optional delay between iterations
    if [ "$DELAY" -gt 0 ] && [ "$i" -lt "$ITERATIONS" ]; then
        sleep "$DELAY"
    fi
done

end_time=$(date +%s)
total_time=$((end_time - start_time))

echo ""
echo "=== Complete ==="
echo "Total iterations: $count"
echo "Total time: ${total_time}s"
echo ""
echo "Check your ClickHouse storage to verify data size."
