<?php

require_once __DIR__ . '/test_utils.php';

function addUserToEmptyShift($conn)
{
    $row_count = execute($conn, 1, date("Y-m-d"), 1);
    custom_assert($row_count, 1);
}

function addUserToFullShift($conn)
{
    prepare($conn, __DIR__ . '/../resources/full-shift.sql');
    $row_count = execute($conn, 1, date("Y-m-d"), 1);
    custom_assert($row_count, 0);
}

function addExperiencedUserToShift($conn)
{
    prepare($conn, __DIR__ . '/../resources/three-people-shift.sql');
    $row_count = execute($conn, 10, date("Y-m-d"), 1);
    custom_assert($row_count, 1);
}

function addExperiencedUserTooManyExperienced($conn)
{
    prepare($conn, __DIR__ . '/../resources/too-many-experienced.sql');
    $row_count = execute($conn, 10, date("Y-m-d"), 1);
    custom_assert($row_count, 0);
}

function addUserNotEnoughExperienced($conn)
{
    prepare($conn, __DIR__ . '/../resources/not-enough-experienced.sql');
    $row_count = execute($conn, 1, date("Y-m-d"), 1);
    custom_assert($row_count, 0);
}

function addUserNotWorkingShift($conn)
{
    prepare($conn, __DIR__ . '/../resources/not-working-shift.sql');
    $row_count = execute($conn, 1, date("Y-m-d"), 2);
    custom_assert($row_count, 1);
}

function addUserNotCounted($conn)
{
    prepare($conn, __DIR__ . '/../resources/not-counted-users.sql');
    $row_count = execute($conn, 1, date("Y-m-d"), 2);
    custom_assert($row_count, 1);
}

function run_assigment_suite($conn)
{
    error_log("Running Assignment Tests...");
    $tests = [
        'addUserToEmptyShift' => addUserToEmptyShift(...),
        'addUserToFullShift' => addUserToFullShift(...),
        'addExperiencedUserToShift' => addExperiencedUserToShift(...),
        'addExperiencedUserTooManyExperienced' => addExperiencedUserTooManyExperienced(...),
        'addUserNotEnoughExperienced' => addUserNotEnoughExperienced(...),
        'addUserNotWorkingShift' => addUserNotWorkingShift(...),
        'addUserNotCounted' => addUserNotCounted(...)
    ];
    foreach ($tests as $name => $test) {
        prepare($conn, __DIR__ . '/../resources/cleanup-after-test.sql');
        try {
            $test($conn);
            error_log("✅ Test $name: PASSED");
        } catch (AssertionError $e) {
            error_log("❌ Test $name: FAILED - " . $e->getMessage());
        }
    }
}
