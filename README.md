A swifty program to help your social media presence on sc

# Install
You will need
- [A terminal emulator](https://en.wikipedia.org/wiki/Terminal_emulator)
- [PHP](https://php.net)
- [Composer](https://getcomposer.org/) 

Either download or clone this repository. 

Copy the `.env.dist` file to `.env` and edit it for your needs. Also edit the comments.txt to personalize your presence.

Run in your terminal, in the project folder:
```
composer install
```
to install the project dependencies

# Run
Find an artist that does music which is similar to yours. Get his SC username (the final part of the URL) - we will call that TARGET

Then run in your terminal, in the project folder:


php bin/console app:commenter TARGET
```

have fun
