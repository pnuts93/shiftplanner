# Shiftplanner
## Introduction
This project has been created to be easily deployable on a web-hosting platform (hence the use of PHP).  
The application is designed to be ON INVITATION ONLY to ensure that only the approved users can use the application. It's mainly a measure to avoid unwanted activity on a tool that is normally for internal use AND to avoid pointless expenses if the project is deployed to cloud.  
Also, it is designed to operate the minimum amount of communication with the backend possible, so that the user experience could be acceptably fluid with a low cost in terms of infrastructure.  
I am open sourcing this project for three main reasons:  
1. For the remote possibility that someone else needs such a software
2. For the more even remote possibility that someone else feels like improving this repository
3. For free version control  

If you find any security related bugs, please send me an email.

## Database compatibility
This project is compatible only with PostgreSQL

## How to deploy
1. Fill in the Angular environment (`shiftplanner-frontend/src/environments/environment.ts`) and compile the project
2. Copy `app.ini.template` (`shiftplanner-backend/private/app.ini.template`) into a file in the same path with the name `app.ini` and fill it in
3. Copy the compiled frontend and the backend to your favorite web-hosting provider's platform
4. ⚠️ Set the folder `private` as protected in the webserver: if this is not done, your application will not be secure, all of the passwords saved there will be accessible to everyone with a connection to the internet and fire will rain from the sky
5. Run `migration.sql` in the database
6. Add a first admin user in the table `approved_users`. E.g.  
```sql
INSERT INTO approved_users (email, is_admin) VALUES (<youremail>, TRUE);
```
7. Now register yourself and you should be good to go

## How to use
Main features:  
1. Registration/login: this comes together with an email confirmation system and a password recovery system.
2. User dashboard: in this page administrators can assign anyone to any shift that respects the threshholds and normal users can assign themselves.
3. Profile: from this component users can change their information and preferred language. At the moment English and German are supported.
4. Admin dashboard: from this component (accessible exclusively to administrators), new approved users can be added, either as normal users or as administrators. It is also possible to delete normal users, but administrators cannot delete or demote other administrators, therefore, if an administrator wants to get off the app, they need to either delete themselves, or they need to demote themselves, and at that point another administrator will be able to delete their profile.