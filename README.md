# UsraRobow_MillionaireChallenge

A PHP-based Who Wants to Be a Millionaire trivia game for CSC 4370 Web Programming Project 2.

Usra Robow, 002591342 - Primary PHP Contributions: The full login.php, register.php, and index.php which involves creating the sessions, usernames and password logics for each user in the game, the multiplayer logic, and making sure the users don't duplicate usernames, password matches, and that the username and password meets their respective requirements,
Alexa Veloz, 002777254 - Primary PHP Contributions: The full game.php, log out feature, leaderboard.php, and scores.php which involves the core game logic, getting rid of a session when logging out, and saving scores to display for the leaderboard.

https://codd.cs.gsu.edu/~aveloz1/web/PW/PW2/UsraRobow_MillionaireChallenge/index.php

Throughout this project, we used PHP and CSS to make a recreation of the well known game called Who Wants To Be A Millionaire while making it multiplayer. We decided to call this project Millionaire Challenge. We decided to choose this topic because we believed that we would have the most fun making this game. Not only that but it's a game we both think we could do with our skill level while doing an idea we both know. We both were rather familiar with the original game as well which factored into our decision.

Set Up Instructions:
1. Create a folder and name it "millionaire_challenge" (or a folder that'll allow you to know what game it is)
2. Add all the files into the folder. How it's structured doesn't matter but it would be best to have it: README.md, index.php, register.php, login.php, game.php, scores.php, leaderboard.php, logout.php, and styles.css.
3. Log into CODD and drag the folder into the server
4. Make sure your PHP version is PHP 8.5.11
5. Finally to access the url set it up as such: https://codd.cs.gsu.edu/~username/foldername (if it's a folder inside a folder, write the outer folder first)/inner folder(if applicable)/index.php


When starting up the website, you first need click the register button. From there you would be brought to a screen, allowing you to register the two players who will play. For the username it must have a length between 3-20 with wither lower case letters, uppercase letters, numbers, or with underscores. Meanwhile, the only requirement for passwords is that it's at least 8 characters long. Once both players are registered, they must click "login" which will lead them to the login page. There, both users will log into their registered accounts. From there, it will take the players will be able to hit "start game" or log out if they wish. How game of play works is a back and forth trivia game. Each player will take turns answering multiple choice questions, given the chance to use life lines if needed. As the game progresses, each player will earn more money, but the questions would become harder. The game will end if the players finishes all the answers correctly, only one player finishes all 15 questions, or both miss on a question. Once the game is finished, they are taken to the leaderboard to show where they rank from the game and previous games if they played before. If the users wants to end the game and leave, they can click the logout button which would take them to a screen asking if they're sure they want to leave. If they click yes, they will be taken back to the homepage and must register again. If they click no, they are returned back to the previous page.
