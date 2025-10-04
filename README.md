# Online-Learning-Platform

## Project Overview
This project is an **Online Learning Platform** designed to provide students with access to courses, study materials, and quizzes, while allowing instructors to manage content and track student progress. The platform supports student registration, login, course enrollment, quiz attempts, and progress tracking. It also includes messaging functionality for instructor-student communication.

**Key Feature Highlight:**  
- **EmailJS OTP Verification:** Student registration includes **OTP verification via EmailJS**, ensuring secure and verified user accounts.

## Setup Instructions

### Prerequisites
- **Web Server**: Apache (XAMPP/WAMP/LAMP recommended)  
- **Database**: MySQL  
- **PHP**: Version 7.4 or higher  
- **Browser**: Any modern browser (Chrome, Firefox, Edge)  

### Steps to Set Up Locally
1. Clone the repository:
   ```bash
   git clone <repository_url>
2. Move the project folder to your web server's root directory (e.g., htdocs for XAMPP).
3. Create a database in MySQL named onlinelearning.
4. Import the SQL file database.sql provided in the repository to set up all required tables.
5. Update database credentials in config.php or any database connection files if necessary:
   ```bash
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "onlinelearning";
6. Start your web server and navigate to:
   ```bash
   http://localhost/<project_folder>

### Running the application
1. Open the browser and go to the project URL (as above).
2. Register as a student or instructor.
3. Students will receive an OTP via EmailJS to verify their email before completing registration.
4. Log in with your credentials.
5. Explore available courses, access study materials, attempt quizzes, and check progress.
6. Instructors can add materials, create quizzes, and send messages to students.

### Project Architecture & Technology choices
1. Frontend: HTML, CSS, JavaScript
   Chosen for simplicity, responsiveness, and ease of integration with PHP backend.
   Includes custom styling for a modern UI and dynamic interactivity.

2. Backend: PHP
   Selected due to its ease of deployment on standard LAMP/WAMP/XAMPP stacks and compatibility with MySQL.
   Handles authentication, database operations, quiz logic, and messaging.

3. Database: MySQL
   Used to store users, courses, quizzes, messages, and progress data.
   Chosen for its reliability, relational structure, and ease of use with PHP.

4. Architecture:
   Follows a modular approach, separating functionality into files like login.php, register.php, get_course_materials.php, and quiz_results.php.
   Ensures maintainability and scalability for future features.

### Screenshots
<img width="1355" height="610" alt="image" src="https://github.com/user-attachments/assets/1c9d8885-ddda-4bc5-8e15-3bc63ea98cc8" />
<img width="1354" height="596" alt="image" src="https://github.com/user-attachments/assets/8d3f8210-bc10-43bc-b4de-2859408e8da7" />
<img width="1351" height="589" alt="image" src="https://github.com/user-attachments/assets/2c489afd-8d11-4adb-bee9-578ce8a50ab8" />
#### Instructor:
<img width="1345" height="584" alt="image" src="https://github.com/user-attachments/assets/a83b26f8-165e-4c82-bd2b-3171a0bb5b89" />
<img width="1349" height="563" alt="image" src="https://github.com/user-attachments/assets/b9f33fd6-5de0-4840-8ff2-951f5b9d998c" />
<img width="1350" height="556" alt="image" src="https://github.com/user-attachments/assets/01efef3f-1263-4ebc-a0fe-6b4ea0aef331" />
<img width="1344" height="550" alt="image" src="https://github.com/user-attachments/assets/8819fa63-4ace-4774-8528-04bbef9053c9" />
<img width="1318" height="587" alt="image" src="https://github.com/user-attachments/assets/d673ca33-3f1c-45c1-9309-0de173d22a03" />
#### Student:
<img width="1348" height="562" alt="image" src="https://github.com/user-attachments/assets/61afe978-420f-4531-a752-72c12fcc5fd4" />
<img width="1343" height="531" alt="image" src="https://github.com/user-attachments/assets/35e30baa-9e59-4d38-bc78-a5f174abe372" />
<img width="1357" height="569" alt="image" src="https://github.com/user-attachments/assets/38b108ea-f3f6-483d-a355-c5301b7a9122" />
<img width="1352" height="566" alt="image" src="https://github.com/user-attachments/assets/80f62536-fe9a-496a-aff3-09a7e3aaaf37" />


