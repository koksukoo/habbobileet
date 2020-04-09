## Habbobileet

# Installation

Follow <a href="https://github.com/devraizer/Cosmic/wiki/Installation---Debian-9,-Morningstar-Arcturus-&-Catalogue---Cosmic">Cosmic installation guide</a>

# Modification

- Add modification files in this repository into their corresponding folder in the server
    - Cosmic/public/assets/js/hotel/currentRoomClient.js
    - Cosmic/App/View/Client/client.html: add tubeplayer elements and link to currentRoomClient.js
    - Cosmic/App/Controllers/Api.php: overwrite this file (it creates currentroom()-function)
    - Cosmic/App/Models/Player.php: overwrite this file (it creates getCurrentRoomById()-function)
- Done!