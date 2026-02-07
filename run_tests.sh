#!/bin/bash

docker run --name shiftplanner_test -e POSTGRES_PASSWORD=postgres -e POSTGRES_USER=postgres -e POSTGRES_DB=shiftplanner -d -p 5432:5432 postgres

# Wait for the database to be ready
until docker exec shiftplanner_test pg_isready -U postgres; do
  echo "Waiting for database to be ready..."
  sleep 2
done
echo "Database is ready."
# Run the tests
php shiftplanner-backend/test/src/tests.php

# Stop and remove the Docker container
docker stop shiftplanner_test
docker rm shiftplanner_test