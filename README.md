# Customizable Web Forms Application: Symfony of my soul

## Project Description

This is a web application designed to allow users to create and manage customizable forms, similar to Google Forms. Users can define "templates" with various question types, and other users can then fill out "forms" based on these templates. The application supports user authentication, role-based access (regular users and administrators), and provides tools for analyzing collected responses.

---

## Features

This application offers a comprehensive set of features to facilitate form creation, distribution, and data collection.

### User Management & Authentication

* **User Registration & Login:** Users can register and authenticate using site forms.
* **Role-Based Access:**
    * **Non-authenticated users:** Can **search** and **view public templates** in read-only mode.
    * **Authenticated users:** Can **create templates**, **fill out forms**, **leave comments**, and **like templates**.
    * **Admins:** Have full **user management** capabilities (view, block, unblock, delete users, add/remove admin roles). An admin can **remove admin access from themselves**.
    * Admins virtually own all pages, allowing them to manage (edit/delete) any template or form created by other users.
* **Personal Pages:** Each authenticated user has a personal page to manage:
    * A **sortable table of their created templates** (create new, delete, edit).
    * A **sortable table of their filled forms**.

### Template Creation & Management

* **Customizable Questions:** Templates support adding various question types:
    * Up to 4 single-line strings.
    * Up to 4 multiple-line texts.
    * Up to 4 non-negative integers.
    * Up to 4 checkboxes.
* **Fixed Fields:** Every form automatically includes "user" (filled automatically) and "date" (filled automatically).
* **Question Properties:** Each question has a title, description, and a boolean flag to indicate if it should be displayed in the aggregated results table.
* **Question Reordering:** Questions can be reordered using **drag-and-drop**.
* **Template Settings:**
    * **Title** and **Description** (with Markdown formatting support).
    * **Topic:** One value from a predefined list (e.g., "Education," "Quiz," "Other").
    * **Optional Image/Illustration:** Users can upload images to cloud storage.
    * **Tags:** Users can enter multiple tags with **autocompletion** support from existing tags.
* **Access Control:**
    * **"Public" templates:** Can be filled by any authenticated user.
    * **"Restricted" templates:** User selects a specific set of registered users to grant access to. User selection includes **autocompletion** for names and emails, and the ability to remove selected users.

### Form Filling & Viewing

* **Form Submission:** Authenticated users with access can fill out templates, saving their responses as "forms."
* **Form Visibility:** Filled forms (answers) can be seen by the form's author, the template's creator, and admins.
* **Management Permissions:**
    * Only the **admin or template creator** can manage (add/delete/edit questions) a template.
    * Only the **admin or form creator** can manage (delete or edit answers) a filled form.
* **Read-Only Access:** The template author can view forms filled by other users in read-only mode.

### Data Analysis & Aggregation

* **Template Page "Results" Tab:** Lists all filled-out forms based on the given template, with links to individual forms.
* **Template Page "Aggregation" Tab:** Provides aggregated results/answers (e.g., average value for numeric fields, most frequent answer for string fields).

### Search & Navigation

* **Full-Text Search:** A search bar in the top header allows full-text searching across templates. Search results link directly to the relevant templates.
* **Main Page:**
    * **Gallery of latest templates** (displaying name, description/image, author).
    * **Table of top 5 most popular templates** (based on the number of filled forms).
    * **Tag cloud:** Clicking a tag displays a list of templates associated with that tag (via the search results page).

### Interaction & UX Enhancements

* **Comments System:** A linear comments list at the bottom of each template page. Comments are automatically updated (with a short delay) when new comments are added.
* **Likes System:** Users can "like" a template (one like per user per template).
* **Responsive Design:** The application is designed to be adaptive and support various screen resolutions, including mobile phones.
* **Optimized UI:** Designed to avoid N+1 button issues by using toolbars or animated context actions.

### Technical & Cross-Cutting Concerns

* **Internationalization:** Supports two languages (English and another chosen language), with UI translation. User's language choice is saved.
* **Theming:** Supports light and dark visual themes. User's theme choice is saved.
* **Optimistic Locking:** Implemented across data update/delete operations to handle concurrent modifications gracefully.
* **ORM Usage:** Leverages Doctrine ORM for efficient and secure data access.
* **Full-Text Search Engine:** Utilizes PostgreSQL's native full-text search capabilities for efficient searching.

---

## Technologies Used

* **Backend:**
    * **PHP 8.2+**
    * **Symfony 7+** (Framework)
    * **Doctrine ORM** (for database interaction)
* **Database:**
    * **PostgreSQL** (Relational Database)
* **Frontend:**
    * **JavaScript / TypeScript** (for interactive elements, AJAX)
    * **Bootstrap 5** (CSS Framework for responsive design and UI components)
    * **Twig** (Templating Engine)
* **Deployment:**
    * **Render** (Cloud Platform)
* **Development Tools:**
    * **Laragon** (Windows development environment for PHP/PostgreSQL)
    * **pgAdmin** (PostgreSQL administration tool)
    * **VS Code** (Code Editor)

---

## Getting Started (Local Development)

Follow these steps to set up and run the application on your local Windows machine using Laragon.

### Prerequisites

* **Laragon (Full Version):** Provides Apache/Nginx, PHP, Composer, and PostgreSQL. Download from [Laragon.org](https://laragon.org/).
* **Node.js & npm/Yarn:** For managing frontend dependencies and compiling assets (if using Webpack Encore). Download from [Node.js](https://nodejs.js.org/).

### Installation Steps

Assuming you have already obtained the project files:

1.  **Start Laragon Services:**
    Launch Laragon and click the **"Start All"** button to start Nginx/Apache, PHP, and PostgreSQL services.

2.  **Configure Database:**
    * In Laragon, right-click on the database icon -> PostgreSQL -> **"Add new database"**.
    * Create a new database for your project (e.g., `forms_app`).
    * Open the `.env` file in your project root.
    * Update the `DATABASE_URL` with your PostgreSQL connection details. Typically, for Laragon, the default user is `postgres` and the password might be empty or `root`.
        ```dotenv
        # .env
        DATABASE_URL="postgresql://postgres:@127.0.0.1:5432/forms_app?serverVersion=16&charset=utf8"
        ```
        If you set a password for the `postgres` user in Laragon, include it: `postgresql://postgres:your_password@127.0.0.1:5432/forms_app?...`

3.  **Install Composer Dependencies:**
    From your project root in the terminal:
    ```bash
    composer install
    ```

4.  **Run Database Migrations:**
    This will create all the necessary tables in your `forms_app` database.
    ```bash
    php bin/console doctrine:migrations:migrate
    ```
    Confirm when prompted.

5.  **Create Initial Admin User (Optional but Recommended):**
    After registering a user via the UI, you can manually update their `roles` column in the `users` table to `["ROLE_ADMIN", "ROLE_USER"]` using pgAdmin to grant them admin privileges for development purposes.

6.  **Build Frontend Assets (If applicable):**
    If the project uses Webpack Encore, you'll need to install Node.js dependencies and build assets:
    ```bash
    npm install # or yarn install
    npm run dev # or yarn dev (for development build)
    # npm run build # or yarn build (for production build)
    ```

7.  **Access the Application:**
    Laragon automatically creates a virtual host for your project based on its directory name. You can usually access your application in your browser at `http://your-project-name.test` (replace `your-project-name` with your actual project directory name in Laragon's `www` folder).

---

## Deployment (Render)

The application is configured for deployment on **Render**.

1.  **Repository Setup:** Ensure your project is pushed to a Git repository accessible by Render.
2.  **Render Web Service:** Create a new "Web Service" on Render and link it to your repository.
3.  **PostgreSQL Database:** Create a new "PostgreSQL" database on Render for your application.
4.  **Environment Variables:**
    * Define the `DATABASE_URL` (provided by Render's PostgreSQL instance) in your Web Service's environment variables.
    * Set your **`APP_SECRET`** (generate a unique, strong key) as an environment variable.
    * Configure credentials for your chosen cloud image storage (e.g., `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`).
    * Any other sensitive configurations from your local `.env` file must be added as environment variables on Render.
5.  **Build & Start Commands:** Configure Render to execute `composer install` and `php bin/console doctrine:migrations:migrate --no-interaction` during the build phase. Provide a suitable start command for Symfony (e.g., `php-fpm`).
6.  **Public Directory:** Ensure the web service's "Public Directory" setting is correctly configured to `/public`.

---

## Usage Guide

1.  **Visit the Home Page:** Browse the latest templates, popular templates, and use the tag cloud.
2.  **Register/Login:** Create a new account or log in to unlock full functionality.
3.  **Create a Template:**
    * Navigate to your personal page (e.g., "My Templates").
    * Click "Create New Template."
    * Fill in general settings, add questions, configure access, and upload an optional image.
4.  **Fill a Form:**
    * Open a public template, or a restricted template you have access to.
    * Click "Fill Out Form" and provide your answers.
5.  **Manage Templates/Forms:**
    * On your personal page, you can edit or delete your templates and filled forms.
    * Template creators and admins can view results and aggregations for their templates.
6.  **Admin Actions:**
    * Log in as an admin.
    * Access the `/admin` route to manage users (block, unblock, delete, change roles).
    * As an admin, you can edit any template or form regardless of ownership.

---

## Optimistic Locking Implementation

This project implements **Optimistic Locking** to ensure data integrity during concurrent updates.

### How it Works

1.  **Version Field:** Relevant database tables (e.g., `templates`, `forms`, `users`) include a `version` column (typically a `BIGINT` integer or a `TIMESTAMP WITH TIME ZONE`).
2.  **Read Operation:** When a record is fetched for editing, its current `version` is also retrieved and sent to the client (often as a hidden form field).
3.  **Update/Delete Operation:** When the user submits changes or attempts to delete a record, the client sends back the `version` that was originally read.
4.  **Concurrency Check:** On the server-side, before applying any changes, the submitted `version` is compared against the record's current `version` in the database.
    * If the versions match, it means the record hasn't been modified by another user since it was loaded. The update/delete proceeds, and the `version` field in the database is incremented (or updated to a new timestamp/GUID).
    * If the versions **do not match**, it indicates a conflict (another user modified the record concurrently). The update/delete operation is rejected.
5.  **Client-Side Handling:** If a conflict occurs, the API returns an appropriate error (e.g., HTTP 409 Conflict). The frontend then displays a user-friendly message, informing the user that the data has been changed by someone else and prompting them to reload the page to see the latest version before trying again.

This mechanism ensures that users don't accidentally overwrite each other's changes and provides a clear path for conflict resolution.

---

## Security Considerations

The application incorporates several fundamental security practices:

* **Password Hashing:** User passwords are securely hashed using Symfony's `auto` algorithm (e.g., bcrypt) to prevent plaintext storage.
* **CSRF Protection:** Cross-Site Request Forgery tokens are automatically generated and validated by Symfony's Form component for all forms, including login.
* **SQL Injection Prevention:** All database interactions are performed via Doctrine ORM, which uses prepared statements by default, mitigating SQL injection risks.
* **XSS Prevention:** Twig templates automatically escape output, protecting against most Cross-Site Scripting vulnerabilities. Markdown content is assumed to be sanitized by the markdown rendering library.
* **.env for Secrets:** Sensitive configurations (database credentials, API keys, application secret) are stored in `.env` files locally and as environment variables in production (Render) to keep them out of version control.
* **Access Control:** Role-based access control is enforced at the controller level using Symfony's Security component, limiting access to features based on user roles.
* **HTTPS:** Render automatically enforces HTTPS for deployed applications, encrypting all communication.

---

## Contributing

For course project purposes, contributions are limited to the project team. However, in a real-world scenario:

1.  Create a new feature branch.
2.  Implement your changes and ensure tests pass.
3.  Submit a Pull Request for review.

---

## License

Distributed under the MIT License. See `LICENSE` for more information.
