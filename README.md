# MiniImageLibrary

A small PHP/Symfony project demonstrating MongoDB integration with Doctrine ODM, focusing on CRUD operations and MongoDB-specific features like GridFS file storage, regex search, and array membership. Built with Docker and Composer, it includes user authentication and image management via a web interface.

---

## 1) Requirements

1. **Docker**
2. **Docker Compose**
3. **(Windows) WSL2**

---

## 2) Installation / Run

1. **Clone the Repository**
   ```
   git clone https://github.com/LuigiG34/MiniImageLibrary
   cd MiniImageLibrary
   ```

2. **Start MongoDB, PHP, and Nginx**
   ```
   docker compose up -d
   ```

3. **Build the PHP Container**
   ```
   docker compose build php
   ```

4. **Install PHP Dependencies**
   ```
   docker compose run --rm php composer install
   ```

5. **Access the Web Application**
   - Open your browser and navigate to `http://localhost:8080`.
   - Register a user at `/register` (e.g., email: `user@example.com`, password: `password`).
   - Log in at `/login` to upload, view, edit, or delete images.

6. **(Optional) Access MongoDB via terminal**
   Connect to MongoDB to inspect the database:
   ```
   docker compose exec mongo mongosh -u root -p root --authenticationDatabase admin
   ```
   Then:
   ```
   use app
   show collections
   db.images.files.find()
   db.images.chunks.find()
   db.users.find()
   ```

7. **Stop the Application**
   ```
   docker compose down
   ```
