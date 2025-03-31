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
      case "home":
        $this->showHome();
        break;
      case "swipelibrary":
        $this->showSwipe();
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

      $results = $this->db->query("select * from users where email = $1;", $_POST["email"]);

      if (empty($results)) {
        // create the user account
        $result = $this->db->query("insert into swipify_users (name, email, password) values ($1, $2, $3);",
        $_POST["fullname"], $_POST["email"], 
        password_hash($_POST["password"], PASSWORD_DEFAULT));
        
        $_SESSION["name"] = $_POST["fullname"];
        $_SESSION["email"] = $_POST["email"];
        
        header("Location: ?command=home");
        return;
      } else {
        $hashed_password = $results[0]["password"];
        $correct = password_verify($_POST["password"], $hashed_password);
        if ($correct) {
          $_SESSION["name"] = $_POST["fullname"];
          $_SESSION["email"] = $_POST["email"];

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
  }

  public function showSwipe($message = "") {
    include("/opt/src/Swipeify/templates/swipeLib.html");
  }

  public function showHome($message = "") {
    include("/opt/src/Swipeify/templates/home.html");
  }

  public function showLogin($message = "") {
    include("/opt/src/Swipeify/templates/login.php");
  }
  
  public function showWelcome($message = "") {
    include("/opt/src/Swipeify/templates/index.html");
  }
}