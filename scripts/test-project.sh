#!/bin/bash

# 1000proxy Complete Test Script (Bash version)
# Comprehensive testing for the entire Laravel application

# Default options
UNIT=true
FEATURE=true
BROWSER=false
API=true
COVERAGE=false
FILTER=""
TESTSUITE=""
VERBOSE=false
STOP_ON_FAILURE=false

# Color output
function color_echo() {
    local color="$1"
    local message="$2"
    case $color in
        red) tput setaf 1 ;;
        green) tput setaf 2 ;;
        yellow) tput setaf 3 ;;
        blue) tput setaf 4 ;;
        magenta) tput setaf 5 ;;
        cyan) tput setaf 6 ;;
        gray) tput setaf 7 ;;
        *) tput sgr0 ;;
    esac
    echo "$message"
    tput sgr0
}

function write_header() {
    echo ""
    color_echo "$1" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    color_echo "$1" "$2"
    color_echo "$1" "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

# Parse arguments
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --unit) UNIT=true ;;
        --no-unit) UNIT=false ;;
        --feature) FEATURE=true ;;
        --no-feature) FEATURE=false ;;
        --browser) BROWSER=true ;;
        --no-browser) BROWSER=false ;;
        --api) API=true ;;
        --no-api) API=false ;;
        --coverage) COVERAGE=true ;;
        --filter) FILTER="$2"; shift ;;
        --testsuite) TESTSUITE="$2"; shift ;;
        --verbose) VERBOSE=true ;;
        --stop-on-failure) STOP_ON_FAILURE=true ;;
    esac
    shift
done

# Begin
write_header cyan "🧪 1000proxy Complete Test Suite"
color_echo gray "Test Started: $(date '+%Y-%m-%d %H:%M:%S')"

# ENV CHECK
write_header yellow "🔍 PRE-TEST ENVIRONMENT CHECK"

if [ ! -f artisan ]; then
    color_echo red "❌ Not in a Laravel project directory"
    exit 1
fi

php artisan test --help &> /dev/null
if [ $? -ne 0 ]; then
    color_echo red "❌ PHPUnit not available. Run 'composer install' first."
    exit 1
fi

color_echo green "✅ Laravel project detected"
color_echo green "✅ PHPUnit available"

if [ -f .env.testing ]; then
    color_echo green "✅ Testing environment file found"
else
    color_echo yellow "⚠️  No .env.testing file found, using default .env"
fi

test_count=$(find tests -name "*.php" | wc -l)
if [ "$test_count" -eq 0 ]; then
    color_echo red "❌ No test files found"
    exit 1
else
    color_echo green "✅ Tests directory found with $test_count test files"
fi

# BUILD TEST COMMAND
PHPUNIT_CMD="php artisan test"
TEST_TYPES=()

if $UNIT && ! $FEATURE && ! $BROWSER; then
    PHPUNIT_CMD+=" --testsuite=Unit"
    TEST_TYPES+=("Unit Tests")
elif $FEATURE && ! $UNIT && ! $BROWSER; then
    PHPUNIT_CMD+=" --testsuite=Feature"
    TEST_TYPES+=("Feature Tests")
elif $BROWSER && ! $UNIT && ! $FEATURE; then
    PHPUNIT_CMD+=" --testsuite=Browser"
    TEST_TYPES+=("Browser Tests")
fi

if [ -n "$TESTSUITE" ]; then
    PHPUNIT_CMD+=" --testsuite=$TESTSUITE"
    TEST_TYPES+=("Custom Suite: $TESTSUITE")
fi

if [ -n "$FILTER" ]; then
    PHPUNIT_CMD+=" --filter=$FILTER"
    TEST_TYPES+=("Filtered: $FILTER")
fi

if $COVERAGE; then
    PHPUNIT_CMD+=" --coverage-html storage/coverage"
    TEST_TYPES+=("with Coverage")
fi

if $VERBOSE; then
    PHPUNIT_CMD+=" --verbose"
fi

if $STOP_ON_FAILURE; then
    PHPUNIT_CMD+=" --stop-on-failure"
fi

if [ "${#TEST_TYPES[@]}" -eq 0 ]; then
    TEST_TYPES+=("All Tests")
fi

write_header cyan "🎯 RUNNING: ${TEST_TYPES[*]}"

# 1. DATABASE PREPARATION
write_header yellow "🗄️ DATABASE PREPARATION"
color_echo cyan "Refreshing test database..."
php artisan migrate:fresh --seed --env=testing &> tmp_db.log
if [ $? -eq 0 ]; then
    color_echo green "✅ Test database refreshed successfully"
else
    color_echo red "❌ Failed to refresh test database"
    cat tmp_db.log
fi
rm -f tmp_db.log

# 2. CACHE CLEARING
write_header yellow "🧹 CLEARING CACHES"
for cmd in config:clear route:clear view:clear cache:clear; do
    php artisan $cmd &> /dev/null
    if [ $? -eq 0 ]; then
        color_echo green "✅ ${cmd} executed"
    else
        color_echo yellow "⚠️  ${cmd} failed"
    fi
done

# 3. RUN TESTS
write_header yellow "🧪 EXECUTING TEST SUITE"
color_echo cyan "Command: $PHPUNIT_CMD"

start_time=$(date +%s)
eval $PHPUNIT_CMD | tee tmp_test.log
exit_code=$?
end_time=$(date +%s)
duration=$((end_time - start_time))

color_echo cyan "Test Duration: ${duration}s"
if [ $exit_code -eq 0 ]; then
    color_echo green "✅ All tests passed!"
else
    color_echo red "❌ Some tests failed (Exit code: $exit_code)"
fi
rm -f tmp_test.log

# 4. API ENDPOINT TESTING
if $API; then
    write_header yellow "🌐 API ENDPOINT TESTING"
    php artisan serve --host=127.0.0.1 --port=8001 --quiet &
    SERVER_PID=$!
    sleep 3

    declare -A endpoints=(
        ["Home Page"]="http://127.0.0.1:8001"
        ["Health Check"]="http://127.0.0.1:8001/api/health"
        ["Admin Panel"]="http://127.0.0.1:8001/admin"
        ["Customer Panel"]="http://127.0.0.1:8001/customer"
    )

    for name in "${!endpoints[@]}"; do
        url="${endpoints[$name]}"
        response=$(curl -s -o /dev/null -w "%{http_code}" "$url")
        if [ "$response" == "200" ]; then
            color_echo green "✅ $name: OK (Status: $response)"
        else
            color_echo yellow "⚠️  $name: Status $response"
        fi
    done

    kill $SERVER_PID
fi

# 5. COVERAGE REPORT
if $COVERAGE; then
    write_header yellow "📊 COVERAGE REPORT"
    if [ -d "storage/coverage" ]; then
        color_echo green "✅ Coverage report generated in storage/coverage/"
        color_echo cyan "📁 file://$(pwd)/storage/coverage/index.html"
    else
        color_echo red "❌ Coverage report not generated"
    fi
fi

# 6. TEST ANALYSIS
write_header yellow "📋 TEST ANALYSIS"
unit_count=$(find tests/Unit -name "*.php" 2>/dev/null | wc -l)
feature_count=$(find tests/Feature -name "*.php" 2>/dev/null | wc -l)
color_echo cyan "📦 Unit Test Files: $unit_count"
color_echo cyan "📦 Feature Test Files: $feature_count"
