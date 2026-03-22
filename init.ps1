Write-Host "Starting CareerBuddy initialization..." -ForegroundColor Green

Write-Host "Building and starting Docker containers..."
docker-compose up -d --build

Write-Host "Installing Laravel. This might take a few minutes..."
docker-compose exec app bash -c "composer create-project --prefer-dist laravel/laravel tmp_app && cp -a tmp_app/. . && rm -rf tmp_app"

Write-Host "Setting up permissions..."
docker-compose exec app bash -c "chown -R www-data:www-data storage bootstrap/cache"

Write-Host "Updating .env file with database credentials..."
# We will just replace the DB_ config in the .env file.
docker-compose exec app bash -c "sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/g' .env"
docker-compose exec app bash -c "sed -i 's/DB_HOST=.*/DB_HOST=db/g' .env"
docker-compose exec app bash -c "sed -i 's/DB_PORT=.*/DB_PORT=3306/g' .env"
docker-compose exec app bash -c "sed -i 's/DB_DATABASE=.*/DB_DATABASE=careerbuddy/g' .env"
docker-compose exec app bash -c "sed -i 's/DB_USERNAME=.*/DB_USERNAME=cb_user/g' .env"
docker-compose exec app bash -c "sed -i '#DB_PASSWORD=.*#d' .env"
docker-compose exec app bash -c "echo 'DB_PASSWORD=cb_password' >> .env"

Write-Host "Generating application key..."
docker-compose exec app php artisan key:generate

Write-Host "CareerBuddy setup completed successfully!" -ForegroundColor Green
Write-Host "You can now access the app at http://localhost:8000"
