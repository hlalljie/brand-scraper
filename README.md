# Style Finder

Search a website for it's brand colors and fonts.

## Setup

### Step 1: Install Dependencies

#### Docker

`docker compose build`

**Note**: Ollama will pull a 2 GB llama3.2 model on build. Sometimes it will fail before downloading, just run the command again.

### Step 2: Run the Project

#### Docker

`docker compose up`

Open the live App by going to http://localhost:8000/

## Requirements

-   Recommended 8GB of Ram, though allocating only 6GB can work
-   Minimum 4 Core modern CPU or GPU with at least 12GB of VRAM, the more CPU allocated the faster you response time.
-   6.5GB of space if running on Docker. 3GB for Ollama and 2GB for llama 3.2B.
-   **If running on docker make sure to allocate these resources in resource settings**

## User Experience

The App starts at a page with an input box to enter a website URL.

![Start Screen](./docs/assets/stylefinder_start.png)

After entering a URL the server will parse the website for data then feed it to Ollama to find the brand colors and fonts (this takes a couple minutes and by default will time out after 10 minutes).

![Parsing Screen](./docs/assets/stylefinder_parsing.png)

After parsing the app will display the results with color previews.

![Results Screen](./docs/assets/stylefinder_results.png)

## Useful Commands

### Backend

See Laravel Logs: `tail -n 200 storage/logs/laravel.log`

Create a php worker: `php artisan queue:work &`

Clear waiting jobs: `php artisan queue:clear`

CHeck if workers are running `cat /proc/*/cmdline 2>/dev/null | grep "queue:work"`


#### Database
To reset the database, delete is, create it, then load it with a migration, then restart your php worker (above).

Delete DB: `rm database/database.sqlite`

Create DB: `touch database/database.sqlite`

Migrate database: `php artisan migrate`

## Common Issues

### Stuck in Validation Phase
This usually happens when backend services are stalled. 

#### Check Ollama by using `ps aux | grep ollama`. 
This should show an `ollama serve` process and if the model is running will show a process like `/usr/local/bin/ollama runner .....`

If serve is not running, use `ollama serve`

If the model is running and it is still stuck on validation then the issue is likely in the service worker.

#### Check Service worker by using `ps aux | grep queue`
This should show a single service worker process called 

### Ollama error
#### Check Ollama by using `ps aux | grep ollama`. 
This should show an `ollama serve` if the server is running

If serve is not running, use `ollama serve`

If the server is running and repeated attempts get the same error, check the logs with
`tail storage/logs/laravel.log` and look for ollama errors.

If serve is not running, use `ollama serve`

### Other errors
Follow the above steps for checking for service worker, checking for ollama running, and reading logs for potential issues. 

If you can't find a solution or if you found a solution to a problem you've ran into, please create an issue or a pull request.