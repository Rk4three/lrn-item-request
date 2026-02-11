# Item Request Application

## Documentation

- [Process Flow Diagram](docs/process_flow.md)
- [User Guide](docs/user_guide.md)
- [Presentation Slides (HTML)](docs/presentation.html)
- [FAQ](docs/faq.md)

These files provide a visual overview of the system architecture, detailed usage instructions per role, and answers to common questions.

## Setup Instructions (Docker & PostgreSQL Migration)

This application has been migrated to use PostgreSQL as the primary database, designed to run in a Docker environment (locally or on Render).

### Prerequisites

- Docker Desktop installed and running.
- Git (optional, for version control).

### Quick Start (Local Docker)

1.  **Start the Application**:
    Open the terminal in the project root folder and run:

    ```bash
    docker-compose up -d --build
    ```

    This will:
    - Build the PHP image with required PostgreSQL extensions.
    - Start a PostgreSQL database container.
    - Initialize the database schema and seed mock data (`docker/init.sql`).
    - Start the web server on port 8080.

2.  **Access the App**:
    Open your browser and navigate to: `http://localhost:8080`

3.  **Login Credentials (Mock Data)**:
    - **Regular User**: `johndoe` / `password123`
    - **Admin**: `admin` / `password123`
    - **Laundry Manager**: `laundry_mgr` / `password123`
    - **Super Approver**: `super_approver` / `password123`

### Deploying to Render

1.  **Create a New Web Service**:
    - Connect your GitHub repository.
    - Select `docker/Dockerfile` (or use the root `Dockerfile` if moved).
    - Render will automatically build the image.

2.  **Environment Variables**:
    Set the following environment variables in Render's dashboard (or use a `.env` file):
    - `DB_HOST`: Your Render PostgreSQL hostname (e.g., `dpg-xxxxx-a`).
    - `DB_NAME`: Your database name.
    - `DB_USER`: Your database username.
    - `DB_PASS`: Your database password.
    - `DB_PORT`: `5432`

3.  **Database Migration**:
    - Ensure your PostgreSQL database on Render has the schema applied. You can run the contents of `docker/init.sql` manually using a SQL client connected to your Render database.

### Troubleshooting

- If `docker-compose up` fails due to port conflicts (e.g., port 5432 or 8080 in use), modify `docker-compose.yml` to map to different host ports.
- Ensure Docker Desktop is running before executing commands.
