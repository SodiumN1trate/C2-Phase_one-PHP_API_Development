# Skill17 - Laravel Base Image

## Idea
This is the base image that you can use for your training. We will also provide this image during the competition.
All the agreed upon libraries in the right versions will be included in this image.

## How to use
You will need a running and up-to-date Docker version on your machine.

## Using the repository
Of course you can also use the repository locally. 
```
composer install
```

## Working with the office image from docker hub
To pull the image
```
docker pull franzstimpfl/skill17-laravel-base
```

To create a container based on the image
```
docker run -d --name laravel-base -p 8000:8000 -v ${PWD}:/app -w /app --link database:database franzstimpfl/skill17-laravel-base
```

## Configuration
Do not forget to adjust your .env file accordingly
