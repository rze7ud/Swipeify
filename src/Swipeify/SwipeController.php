<?php

class SwipeController {

  private $db;

  private $errorMessage = "";

  /**
   * Constructor
   */
  public function __construct($input) {
    // Start the session!
    session_start();
    if (isset($_COOKIE["user_name"])) {
        $_SESSION["name"] = $_COOKIE["user_name"]; // Restore session from cookie
    }
    
    $this->db = new Database();

    $this->input = $input;
  }

  /**
   * Run the server
   * 
   * Given the input (usually $_GET), then it will determine
   * which command to execute based on the given "command"
   * parameter.  Default is the welcome page.
   */
  public function run() {
    // Get the command
    $command = "welcome";
    if (isset($this->input["command"]) && (
      $this->input["command"] == "login" || 
      $this->input["command"] == "showlogin" ||
      isset($_SESSION["name"])))
      $command = $this->input["command"];

    switch($command) {
      case "showlogin":
        $this->showLogin();
        break;
      case "login":
        $this->login();
        break;
      case "addsong":
        $this->addSong();
      case "home":
        $this->showHome();
        break;
      case "swipelibrary":
        $this->showSwipeLib();
        break;
      case "search":
        $this->showSearch();
        break;
      case "searching":
        $this->getSearch();
        break;
      case "logout":
        $this->logout();
      case "welcome":
      default:
        $this->showWelcome();
        break;
    }
  }

  public function login() {
    if (isset($_POST["fullname"]) && isset($_POST["email"]) &&
      isset($_POST["password"]) && !empty($_POST["password"]) &&
      !empty($_POST["fullname"]) && !empty($_POST["email"])) {

      $results = $this->db->query("select * from swipeify_users where email = $1;", $_POST["email"]);

      if (empty($results)) {
        // create the user account
        $result = $this->db->query("insert into swipeify_users (name, email, password) values ($1, $2, $3);",
        $_POST["fullname"], $_POST["email"], 
        password_hash($_POST["password"], PASSWORD_DEFAULT));
        
        $_SESSION["name"] = $_POST["fullname"];
        $_SESSION["email"] = $_POST["email"];
        
        // https://www.w3schools.com/php/php_cookies.asp
        setcookie("user_name", $_SESSION["name"], time() + (86400 * 30), "/");

        header("Location: ?command=home");
        return;
      } else {
        $hashed_password = $results[0]["password"];
        $correct = password_verify($_POST["password"], $hashed_password);
        if ($correct) {
          $_SESSION["name"] = $_POST["fullname"];
          $_SESSION["email"] = $_POST["email"];

          setcookie("user_name", $_SESSION["name"], time() + (86400 * 30), "/");

          header("Location: ?command=home");
          return;
        } else {
         $message = "<p class='alert alert-danger'>Incorrect password!</p>"; 
        }
      }
      $this->showLogin($message);
      return;
    }

    header("Location: ?command=showlogin");
    $this->showLogin("Name or email missing");
  }

  /**
   * Logout function.  We **need** to clear the session somehow.
   * When the user wants to start over, we should allow them to
   * reset the game.  I'll call it logout, so this function destroys
   * the session and immediately starts a new one.  (new!)
   */
  public function logout() {
    session_destroy();
    session_start();
    setcookie("user_name", "", time() - 3600, "/");
  }

  public function addSong() {
    if (isset($_POST["songname"]) && isset($_POST["songid"]) && isset($_POST["artist"]) && isset($_POST["album"])) {
      $this->db->query("INSERT INTO tracks (spotify_id, name) VALUES ($1, $2)", $_POST["songid"], $_POST["songname"]);
      $result = $this->db->query("select id from swipeify_users where email = $1 LIMIT 1;", $_SESSION["email"]);
      if ($result) {
        $_SESSION["curuserid"] = $result[0]["id"];
      }
      $this->db->query("INSERT INTO user_tracks (user_id, track_id) VALUES ($1, $2)", $_SESSION["curuserid"], $_POST["songid"]);
      echo "Your song has been added!";
    }
  }

  public function getSongs() {
    $_SESSION["curuserid"] = $this->db->query("select id from swipeify_users where email = $1;", $_SESSION["email"]);
    $results = $this->db->query("SELECT 
        tracks.name AS song_name, 
        artists.name AS artist_name, 
        albums.name AS album_name
    FROM user_tracks
    JOIN tracks ON user_tracks.track_id = tracks.spotify_id
    JOIN album_tracks ON tracks.spotify_id = album_tracks.track_id
    JOIN albums ON album_tracks.album_id = albums.spotify_id
    JOIN artist_albums ON albums.spotify_id = artist_albums.album_id
    JOIN artists ON artist_albums.artist_id = artists.spotify_id
    WHERE user_tracks.user_id = $1;", $_SESSION["curuserid"]);

    return $results;
  }

  public function getSearch($message = "") {
    // include("/opt/src/Swipeify/templates/swipeLib.html");
    include("/students/rze7ud/students/rze7ud/private/Swipeify/templates/search.html");
  }

  public function showSwipeLib($message = "") {
    // include("/opt/src/Swipeify/templates/swipeLib.html");
    include("/students/rze7ud/students/rze7ud/private/Swipeify/templates/swipeLib.html");
  }
  public function showSearch($message = "") {
    // include("/opt/src/Swipeify/templates/swipeLib.html");
    include("/students/rze7ud/students/rze7ud/private/Swipeify/templates/search.html");
  }

  public function showHome($message = "") {
    // include("/opt/src/Swipeify/templates/home.html");
    include("/students/rze7ud/students/rze7ud/private/Swipeify/templates/home.php");
  }

  public function showLogin($message = "") {
    // include("/opt/src/Swipeify/templates/login.php");
    include("/students/rze7ud/students/rze7ud/private/Swipeify/templates/login.php");
  }
  
  public function showWelcome($message = "") {
    // include("/opt/src/Swipeify/templates/index.html");
    include("/students/rze7ud/students/rze7ud/private/Swipeify/templates/index.html");
  }
}