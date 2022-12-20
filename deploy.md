# Fresh

1. clone the repo

```
git clone https://github.com/kwarnkham/facile.git
cd facile
```

2. fill up env

```
cp .env.example ./.env
nano .env
```

3. install docker

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

4. build and deploy

```
sudo docker-compose -f docker-compose.prod.yml build
sudo docker-compose -f docker-compose.prod.yml run --rm composer install --optimize-autoloader --no-dev --ignore-platform-reqs
sudo docker-compose -f docker-compose.prod.yml run --rm npm install
sudo docker-compose -f docker-compose.prod.yml run --rm artisan key:generate --force
sudo docker-compose -f docker-compose.prod.yml run --rm npm run build
sudo docker-compose -f docker-compose.prod.yml run --rm artisan optimize
sudo docker-compose -f docker-compose.prod.yml run --rm artisan view:cache
sudo docker-compose -f docker-compose.prod.yml up
sudo docker-compose -f docker-compose.prod.yml exec nginx id facile
sudo chmod -R 755 storage bootstrap/cache
sudo docker-compose -f docker-compose.prod.yml run --rm artisan migrate:fresh --seed --force
```

4. build and deploy without docker

```
sudo apt-get install php8.1-gd
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan config:cache
php artisan optimize
php artisan route:cache
php artisan view:cache
create database
php artisan migrate --seed
sudo chown -R www-data.www-data /var/www/facile/storage /var/www/facile/bootstrap/cache
sudo chmod -R 755 /var/www/facile/storage /var/www/facile/bootstrap/cache
install node and npm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.38.0/install.sh | bash
source ~/.bashrc
nvm list-remote
nvm install v16.19.0
nvm current
nvm list
npm i
npm run build
```

# Update

```
sudo docker-compose -f docker-compose.prod.yml run --rm artisan down
git pull
sudo docker-compose -f docker-compose.prod.yml down
sudo docker-compose -f docker-compose.prod.yml run --rm composer install --optimize-autoloader --no-dev --ignore-platform-reqs
sudo docker-compose -f docker-compose.prod.yml up
sudo docker-compose -f docker-compose.prod.yml run --rm artisan migrate
sudo docker-compose -f docker-compose.prod.yml run --rm artisan up

php artisan config:cache
php artisan optimize
php artisan route:cache
php artisan view:cache
```

## Javascript

```
sudo docker-compose -f docker-compose.prod.yml run --rm npm install
sudo docker-compose -f docker-compose.prod.yml run --rm npm run build

npm i
npm run build
cp public/manifest.webmanifest public/build
```

## PHP

```
sudo docker-compose -f docker-compose.prod.yml run --rm artisan migrate --force
sudo docker-compose -f docker-compose.prod.yml run --rm artisan up

composer install --optimize-autoloader --no-dev
php artisan migrate
```

```
sudo chown -R 1000:1000 storage bootstrap/cache
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
docker rmi $(docker images -f "dangling=true" -q)
deluser --remove-home newuser
sudo systemctl status certbot.timer
git config credential.helper store
```
