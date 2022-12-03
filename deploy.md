# Fresh

1. fill up env
2. install docker
3. `docker-compose -f docker-compose.prod.yml up --build`
4. `docker-compose -f docker-compose.prod.yml run --rm artisan key:generate --force`
5. `docker-compose -f docker-compose.prod.yml run --rm artisan migrate:fresh --seed --force`

# Update

1. `docker-compose -f docker-compose.prod.yml restart`
