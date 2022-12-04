# Fresh

1. fill up env

```
cp .env.example ./.env
nano .env

```

2. install docker

```
sudo apt update
sudo apt install apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu focal stable"
apt-cache policy docker-ce
sudo apt install docker-ce
sudo systemctl status docker
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version
```

3. add user for docker

```
adduser newuser
usermod -aG sudo newuser
```

4. build and deploy

```
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml run --rm composer install --optimize-autoloader --no-dev --ignore-platform-reqs
docker-compose -f docker-compose.prod.yml run --rm npm install
docker-compose -f docker-compose.prod.yml run --rm artisan key:generate --force
docker-compose -f docker-compose.prod.yml run --rm npm run build
docker-compose -f docker-compose.prod.yml run --rm artisan optimize
docker-compose -f docker-compose.prod.yml run --rm artisan view:cache
docker-compose -f docker-compose.prod.yml up
docker-compose -f docker-compose.prod.yml run --rm artisan migrate:fresh --seed --force
chown -R facile:facile storage
chown -R facile:facile bootstrap/cache

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

# Server

```
watch -n 5 free -m
df -h
```
