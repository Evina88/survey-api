# Survey Feedback API

A minimal Laravel 10 backend service implementing the requirements for the **E-Satisfaction Survey Feedback API assessment**.  

This service allows responders to view surveys, answer questions, and securely authenticate using JWTs.

---

## 📋 Features

- **JWT-based Authentication** for responders (`/api/register`, `/api/login`, `/api/me`).
- **Survey management** with models & migrations:

### Survey
- `id` (bigint, PK)  
- `title` (string)  
- `description` (text, nullable)  
- `status` (enum/string: active|inactive)  
- `created_at` (timestamp)  
- `updated_at` (timestamp)  

### Question
- `id` (bigint, PK)  
- `survey_id` (bigint, FK → surveys.id, on delete cascade)  
- `type` (enum/string: text|scale|multiple_choice)  
- `question_text` (text)  
- `created_at` (timestamp)  
- `updated_at` (timestamp)  

### Answer
- `id` (bigint, PK)  
- `question_id` (bigint, FK → questions.id, on delete cascade)  
- `responder_id` (bigint, FK → responders.id, on delete cascade)  
- `response_data` (json, cast to array in Eloquent)  
- `created_at` (timestamp)  
- `updated_at` (timestamp)  

### Responder
- `id` (bigint, PK)  
- `email` (string, unique)  
- `password` (string, hashed)  
- `created_at` (timestamp)  
- `updated_at` (timestamp)  

---

## 📡 Endpoints

- `GET /api/surveys` → List active surveys  
- `GET /api/surveys/{id}` → Get survey details + questions  
- `POST /api/surveys/{id}/submit` → Submit answers (**auth required**)  
- `GET /api/me` → Current logged-in responder (**auth required**)  

---

## ✅ Validation

- **Text answers** → non-empty strings (max 2000 chars)  
- **Scale answers** → integers 1–5  
- **Multiple choice answers** → must be one of the allowed options  

---

## 🎁 Bonus Features

- **Rate limiting** → default 30 requests/min per user/IP (`api` middleware).  
- **Elasticsearch logging** → every submission is pushed asynchronously to the configured index (`survey-submissions`).  

> **Important**: You must run the Postman request **Elasticsearch → “Create index (one-time, safe mapping)”** first.  
This creates the `survey-submissions` index with correct mapping so mixed answer types (scale/text/multiple choice) won’t conflict.

---

## 🚀 Installation & Setup

### Requirements
- PHP ≥ 8.3  
- Composer  
- MySQL (or MariaDB)  
- Elasticsearch 8.x (for bonus logging)  

---
### Steps

**1. Clone the repository**
git clone https://github.com/YOUR_USERNAME/survey-api.git
cd survey-api

**2. Install dependencies**
composer install

**3. Copy the example environment file and set credentials**
cp .env.example .env
php artisan key:generate


**Minimum required env vars:**

DB_CONNECTION=mysql

DB_HOST=127.0.0.1

DB_PORT=3306

DB_DATABASE=survey_api

DB_USERNAME=root

DB_PASSWORD=secret


JWT_SECRET=your_jwt_secret_key


# Elasticsearch
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_HOST=http://localhost:9200
ELASTICSEARCH_INDEX=survey-submissions
ELASTICSEARCH_TIMEOUT=3


4. Run migrations & seeders
php artisan migrate --seed


5. Start the server
php artisan serve
# http://127.0.0.1:8000

Elasticsearch Setup 
1. Install Docker Desktop

Download and install Docker Desktop:
👉 https://www.docker.com/products/docker-desktop

Make sure Docker Desktop is running before continuing.

2. Run Elasticsearch container

Open your terminal and run:

docker run --name es-dev \
  -p 9200:9200 \
  -e discovery.type=single-node \
  -e xpack.security.enabled=false \
  -e ES_JAVA_OPTS="-Xms512m -Xmx512m" \
  docker.elastic.co/elasticsearch/elasticsearch:8.14.0

  Keep that terminal/window open while you test. If you need to stop later:
    docker stop es-dev
    docker rm es-dev

---    
### 📬 Postman Collection

A ready-to-use Postman setup is included in the /postman folder.

survey-api.postman_collection.json → endpoints (/register, /login, /me, /surveys, /submit, etc.)

survey-api.postman_environment.json → environment variables (base_url, access_token, Elasticsearch host/index).

**How to Use**
Open Postman.

Import both JSON files from /postman.

Set base_url (default: http://127.0.0.1:8000).

Register → Login → Token is stored automatically → Access protected endpoints.

---
✅ Endpoints Summary

Endpoint	                Method	    Auth	 Description

/api/register	            POST	    ❌	    Register a responder
/api/login	                POST	    ❌	    Login, returns JWT
/api/me	                    GET	        ✅	    Get current responder
/api/surveys	            GET	        ❌	    List active surveys 
/api/surveys/{id}	        GET	        ✅	    Survey details + questions
/api/surveys/{id}/submit	POST	    ✅	    Submit survey answers

--
📝 Notes
Passwords are hashed with bcrypt.

JWT tokens are generated with tymon/jwt-auth.

Surveys and questions are seeded for immediate testing.
