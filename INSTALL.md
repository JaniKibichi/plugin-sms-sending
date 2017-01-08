# Installation

You will need a working playSMS to begin with, let us assume below items are your installation facts:

- Your playSMS web files is in `/var/www/html/playsms`

Follow below steps in order:

1. Clone this repo to your playSMS server

   ```
   cd ~
   git clone https://github.com/JaniKibichi/plugin-sms-sending.git africastalking
   cd africastalking
   ls -l
   ```

2. Copy gateway to playSMS `plugin/gateway/`

   ```
   cp -rR path/to/africastalking /var/www/html/playsms/plugin/gateway/
   ```

3. Restart `playsmsd`

   ```
   playsmsd restart
   playsmsd check
   ```
