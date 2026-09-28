#!/bin/sh

set -eu

if [ "${ALLOW_PRODUCTION_CURRICULUM_SEED:-}" != "1" ]; then
    echo "Defina ALLOW_PRODUCTION_CURRICULUM_SEED=1 para cargar el currículo oficial." >&2
    exit 64
fi

docker compose -f compose.prod.yaml -f compose.bootstrap.yaml run --rm \
    -e APP_ENV=local \
    app sh -eu -c '
        php artisan config:clear

        for seeder in \
            SafeCrossingPilotSeeder \
            VisibleCyclingPilotSeeder \
            ResponsiblePassengerPilotSeeder \
            VisibleMotorcyclingPilotSeeder \
            PreventiveDrivingPilotSeeder \
            SustainableMobilityPilotSeeder \
            SafeSchoolEnvironmentPilotSeeder \
            SafeIncidentResponsePilotSeeder \
            RoadSafetyFamilyPilotSeeder \
            NextRoadEducationCoursesSeeder \
            AdditionalRoadEducationCoursesSeeder \
            ThirdRoadEducationCoursesSeeder \
            FourthRoadEducationCoursesSeeder \
            FifthRoadEducationCoursesSeeder
        do
            php artisan db:seed --force --class="Database\\Seeders\\${seeder}"
        done
    '
