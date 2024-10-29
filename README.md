# Aditude 
Wanna run your own digital ad server? Well NOW YOU CAN! Just clone my repository, setup PHP with your web server, and you can make BILLIONS! (Maybe.)


![Screenshot](/preview01.jpg?raw=true "Preview-1")

### Introduction
Aditube is an ad-server software that manages ads on websites. It is designed for use in various scenarios:

- **Website Owner**: A webmaster who owns a website can add banners, either selling directly to advertisers or by loading banners themselves as the administrator.
- **Publisher**: A publisher who owns one or more websites can add banners, selling directly to advertisers or by loading banners themselves.
- **Media Company**: A media company that operates a network of websites can sell banners across all sites in the network, track payments, and allow webmasters to configure ad positions and advertisers to buy ads.

### Features

- Supports banners in **GIF**, **JPG**, **PNG**, **HTML5** formats, and external scripts (e.g., Google Adsense).
- Easy creation of responsive banners without code using templates.
- HTML5 banners uploaded as ZIP files (HTML, CSS, JavaScript).
- Banners are rotated in positions automatically.
![Screenshot](/preview02.jpg?raw=true "Preview-2")
![Screenshot](/preview03.jpg?raw=true "Preview-3")

### Installation

1. **Requirements (IMPORTANT)**
   - Ensure that the necessary server requirements are met.
   
2. **File Installation and Permissions**
   - Unzip the files and place them in the correct directory.
   - Ensure file permissions are set to allow writing (e.g., CHMOD 755 or CHMOD 777).

3. **Configuring Database Connection**
   - After unzipping the file, navigate to the URL of the folder where Aditube is installed, e.g., `http://www.yourdomain.com/yourfolder`.
   - Insert your database credentials and click “Proceed” to finalize the setup.

### How It Works

Aditube allows you to manage users (webmasters and advertisers) and banners. You can create positions and manage multiple banners per position with automatic rotation.

### Banner Creation

- **Supported Formats**: GIF, JPG, PNG, HTML5, or external scripts (like Google Adsense).
- **HTML5 Banners**: Uploaded as ZIP files with HTML, CSS, and JavaScript. Can contain videos and animations.
  
### Shortcodes for Banners

To simplify the creation of banners, Aditube uses shortcodes that are replaced during delivery. Common shortcodes include:

- `[CLICKTAG]` - Tracks clicks on banners.
- `[ID]` - Displays the banner's ID.
- `[TIMESTAMP]` - Replaced with the current timestamp.

### First Login

Upon the first login, several example banners, positions, campaigns, and clients are created to get started.

### Selling Ads
![Screenshot](/preview04.jpg?raw=true "Preview-4")
Aditube supports selling ads via **PayPal** and **Coinbase** (crypto payments). After configuring a payment gateway, users can sign in, create campaigns, and buy ads through a straightforward process.

1. The advertiser creates a banner.
2. They specify the budget, and the system calculates views or time duration.
3. Banners are published automatically after payment if configured.

### Multilanguage Support

Aditube supports multiple languages. To configure this, follow the instructions provided in the `Config` section.

### Customization

You can customize the login page, add external login methods, or define sellable positions. The **Menu Editor** allows administrators to add custom items to the menu, such as links to information pages or database management tools.
