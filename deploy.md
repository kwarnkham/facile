# Fresh

1. fill up env
2. install docker

```
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up
docker-compose -f docker-compose.prod.yml run --rm composer install --optimize-autoloader --no-dev --ignore-platform-reqs
docker-compose -f docker-compose.prod.yml run --rm artisan key:generate --force
docker-compose -f docker-compose.prod.yml run --rm artisan migrate:fresh --seed --force
docker-compose -f docker-compose.prod.yml run --rm artisan optimize
docker-compose -f docker-compose.prod.yml run --rm artisan view:cache
docker-compose -f docker-compose.prod.yml run --rm npm install
docker-compose -f docker-compose.prod.yml run --rm npm run build
```

# Update

## Javascript

```
docker-compose -f docker-compose.prod.yml run --rm npm install
docker-compose -f docker-compose.prod.yml run --rm npm run build
```

## PHP

```
docker-compose -f docker-compose.prod.yml run --rm composer install --optimize-autoloader --no-dev --ignore-platform-reqs
docker-compose -f docker-compose.prod.yml run --rm artisan migrate --force
docker-compose -f docker-compose.prod.yml run --rm artisan optimize
docker-compose -f docker-compose.prod.yml run --rm artisan view:cache
docker-compose -f docker-compose.prod.yml restart
```

# Domain used in

1. env file
2. DO space cdn
3. default.conf
4. DO space config
