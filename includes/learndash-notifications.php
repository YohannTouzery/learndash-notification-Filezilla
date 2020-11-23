<?php
/**
 * Plugin Name: LearnX-Notifications
 * Plugin URI: 
 * Description:	Create and send notification emails to the users. 
 * Version: 1.4.1
 * Author: LearnX
 * Author URI: https://learnx.fr/
 * Text Domain: learndash-notifications
 * Domain Path: languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Check if class name already exists
if ( ! class_exists( 'LearnDash_Notifications' ) ) :

/**
* Main class
*
* @since 1.0
*/
final class LearnDash_Notifications {
	
	/**
	 * The one and only true LearnDash_Notifications instance
	 *
	 * @since 1.0
	 * @access private
	 * @var object $instance
	 */
	private static $instance;

	/**
	 * Instantiate the main class
	 *
	 * This function instantiates the class, initialize all functions and return the object.
	 * 
	 * @since 1.0
	 * @return object The one and only true LearnDash_Notifications instance.
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ( ! self::$instance instanceof LearnDash_Notifications ) ) {

			self::$instance = new LearnDash_Notifications();
			self::$instance->setup_constants();
			
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			self::$instance->includes();
			add_action( 'plugins_loaded', array( self::$instance, 'includes_after_plugins_loaded' ), 99 );
		}

		return self::$instance;
	}	

	/**
	 * Function for setting up constants
	 *
	 * This function is used to set up constants used throughout the plugin.
	 *
	 * @since 1.0
	 */
	public function setup_constants() {

		// Plugin version
		if ( ! defined( 'LEARNDASH_NOTIFICATIONS_VERSION' ) ) {
			define( 'LEARNDASH_NOTIFICATIONS_VERSION', '1.4.1' );
		}

		// Plugin file
		if ( ! defined( 'LEARNDASH_NOTIFICATIONS_FILE' ) ) {
			define( 'LEARNDASH_NOTIFICATIONS_FILE', __FILE__ );
		}		

		// Plugin folder path
		if ( ! defined( 'LEARNDASH_NOTIFICATIONS_PLUGIN_PATH' ) ) {
			define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL
		if ( ! defined( 'LEARNDASH_NOTIFICATIONS_PLUGIN_URL' ) ) {
			define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Load text domain used for translation
	 *
	 * This function loads mo and po files used to translate text strings used throughout the 
	 * plugin.
	 *
	 * @since 1.0
	 */
	public function load_textdomain() {

		// Set filter for plugin language directory
		$lang_dir = dirname( plugin_basename( LEARNDASH_NOTIFICATIONS_FILE ) ) . '/languages/';
		$lang_dir = apply_filters( 'learndash_notifications_languages_directory', $lang_dir );

		// Load plugin translation file
		load_plugin_textdomain( 'learndash-notifications', false, $lang_dir );
		
		// Include support for new LearnDash Translation logic in v2.5.5
		// This needs to load after LearnDash core because it depends on the LearnDash_Settings_Section and LearnDash_Translations classes
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/admin/class-ld-translations-notifications.php';
	}

	/**
	 * Includes all necessary PHP files
	 *
	 * This function is responsible for including all necessary PHP files.
	 *
	 * @since  0.1
	 */
	public function includes() {
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/functions.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/logger.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/activation.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/cron.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/deactivation.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/database.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/meta-box.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/notification.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/post-type.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/shortcode.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/tools.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/update.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/user.php';
		include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/subscription-manager.php';
	}

	public function includes_after_plugins_loaded()
	{
		if ( is_admin() ) {
			include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/admin/class-settings.php';
			include LEARNDASH_NOTIFICATIONS_PLUGIN_PATH . '/includes/admin/class-status-page.php';
		}
	}
}

endif; // End if class exists check

/**
 * The main function for returning instance
 *
 * @since 1.0
 * @return object The one and only true instance.
 */
function learndash_notifications() {
	return LearnDash_Notifications::instance();
}

// Run plugin
learndash_notifications();



/** Step 2 (from text above). */
add_action( 'admin_menu', 'my_plugin_menu' );

/** Step 1. */
function my_plugin_menu() {
	add_options_page( 'My Plugin Options', 'Log Admin notification', 'manage_options', 'log-admin-notification', 'my_plugin_options' );
}

/** Step 3. */
function my_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'Vous ne disposez pas des autorisations suffisantes pour accéder à cette page.' ) );
	}

	// On détermine sur quelle page on se trouve
	if(isset($_GET['num_page']) && !empty($_GET['num_page'])){
		$currentPage = (int) strip_tags($_GET['num_page']);
		$num_offset = $currentPage-1;
	}else{
		$num_offset = 0;
    	$currentPage = 1;
	}

	$text_url="./options-general.php?page=log-admin-notification&num_page=";
	echo '<div class="wrap">';
	echo '<h2>Tableau de Logs</h2>';
	echo '</div>';
	//Sélection de toutes les données de la table job_learnx_notification_log et affichage dans un tableau

	global $wpdb, $table_prefix, $current_user;


	//ajoute une notification dans la base de données de log
	/*
	$Datetoday = date("Y-m-d")." à ".date("H:i:s"); 

	$resultat_insert = $wpdb->query("INSERT INTO `".$table_prefix."learnx_notification_log` (`notification`, `date`, `destinataires`)
		VALUES
		('TEST 2', '".$Datetoday."', '".$current_user->user_login."');"
		);
	*/
	
	//on récupère le nombre de notifications afin de pouvoir déterminer le nombre de page que doit contenir le tableau

	$nombre_notifications_select = $wpdb->get_results("SELECT COUNT(notification) AS nb_notifications FROM `".$table_prefix."learnx_notification_log`");
	
	//retourne le nombre de notification dans la table job_learnx_notification_log
	foreach($nombre_notifications_select as $result_notification)
		$nb_notifications = $result_notification->nb_notifications;

//echo $nb_notifications;

	// On détermine le nombre d'articles par page
	$notification_par_page = 10;

	// On calcule le nombre de pages total
	$nb_pages = ceil($nb_notifications / $notification_par_page);

	$num_offset = $num_offset*10;


	if(!empty($_GET['champs']) && !empty($_GET['ordre'])){
		$trier_tableau = " ORDER BY ".$_GET['champs']." ".$_GET['ordre'];
	}else{
		$trier_tableau="";
	}

	//Récupération du paramètre 'notification' ou 'destinataire' passé en paramètre dans l'url
	if(!empty($_GET['nom_entete'])){

		$recherche = " WHERE ".$_GET['type_entete']." = '".$_GET['nom_entete']."'";

	}else{
		$recherche="";
	}

	//Requête SQL construit avec des variables en fonction des paramètres récupérés dans l'url
		if($num_offset==0){
		
			$resultat_select = $wpdb->get_results("SELECT notification, UNIX_TIMESTAMP(date) as date, destinataires FROM `".$table_prefix."learnx_notification_log`".$recherche." ".$trier_tableau." LIMIT 10");

		
		
		}else{
		
			$resultat_select = $wpdb->get_results("SELECT notification, UNIX_TIMESTAMP(date) as date, destinataires FROM `".$table_prefix."learnx_notification_log`".$recherche." ".$trier_tableau." LIMIT 10 , ".$num_offset);

		
		}

	//print_r($resultat_select);

	//Change l'heure locale pour adapter l'heure à la zone Europe/Paris
	date_default_timezone_set('Europe/Paris');


	//affichage du tableau de log

	if($_GET['ordre'] == "ASC"){
		$ordre="DESC";
	}else{
		$ordre ="ASC";
	}

	//Envoi dans l'url la notification sélectionné
	echo "<form method='GET' action=''>";
	echo "<input name='page' type='hidden' value='log-admin-notification'>";
	echo "<input name='num_page' type='hidden' value='".$currentPage."'>";
	echo "<input name='type_entete' type='hidden' value='notification'>";
	echo "<label>Triez par Notification :<br />";
		echo"<select name='nom_entete'>";
			$arr=array();
			foreach($resultat_select as $result){
				if(!isset($arr[$result->notification]))
				{
					$arr[$result->notification]=1;
					echo"<option value='".$result->notification."'>".$result->notification."</option>";
				}
			}      
    	echo"</select>";
	echo"</label>";
	echo"<input type='submit' value='Rechercher' />";
	echo"</form>";

	//Envoi dans l'url le destinataire sélectionné
	echo"<form method='GET' action=''>";
	echo "<input name='page' type='hidden' value='log-admin-notification'>";
	echo "<input name='num_page' type='hidden' value='".$currentPage."'>";
	echo "<input name='type_entete' type='hidden' value='destinataires'>";
	echo"<label>Triez par Destinataires :<br />";
		echo"<select name='nom_entete'>";
			$arr=array();
			foreach($resultat_select as $result){
				if(!isset($arr[$result->destinataires]))
				{
					$arr[$result->destinataires]=1;
					echo"<option value='".$result->destinataires."'>".$result->destinataires."</option>";
				}
			}  
            
        echo"</select>";
	echo"</label>";
	echo"<input type='submit' value='Rechercher' />";
    //<input type="reset" value="Rétablir" />
	echo"</form>";

//Affiche le tableau de log 
	//Affiche les entêtes	
	echo "<table border=1>";
		echo "<tr>";
			echo "<th><a href='".$text_url.$currentPage."&champs=notification&ordre=".$ordre."'>Notification</a></th>";
			echo "<th><a href='".$text_url.$currentPage."&champs=date&ordre=".$ordre."'>Date</a></th>";
			echo "<th><a href='".$text_url.$currentPage."&champs=destinataires&ordre=".$ordre."'>Destinataires</a></th>";
		echo "</tr>";
	//Affiche les données
	foreach($resultat_select as $result)
	{
		echo "<tr>";
	   		echo "<td>".$result->notification."</td>";
	   		echo "<td>".date("d-m-Y H:i:s",$result->date)."</td>";
	   		echo "<td>".$result->destinataires."</td>";
   		echo "</tr>";
	}
	echo "</table>";

	echo "<nav>";
		echo "<ul>";
			// Lien vers la page précédente (désactivé si on se trouve sur la 1ère page)
			//echo $currentPage;
			if($currentPage == 1){
            
			}else{
				$currentPage = $currentPage - 1;
				echo "<li>";
                		echo"<a href='".$text_url.$currentPage."'>Précédente</a>";
				echo"</li>";
			}

			echo"<li>";

            for($page = 1; $page <= $nb_pages; $page++){
			// Lien vers chacune des pages (activé si on se trouve sur la page correspondante)
				
                echo"<button><a href='".$text_url.$page."'> ".$page." </a></button>";
					
			}

			echo"</li>";

			// Lien vers la page suivante (désactivé si on se trouve sur la dernière page) 
			if($currentPage == $nb_pages){

			}else{
				$currentPage = $currentPage + 1;
				echo"<li>";
					echo"<a href='".$text_url.$currentPage."' >Suivante</a>";
				echo"</li>";
			}
/*
			foreach($resultat_select as $result)
	{
		echo date("d-m-Y H:i:s",$result->date)."<br>";

	}

			echo $nb_notifications;
            print_r($nombre_notifications_select);
*/	
		echo "</ul>";
    echo"</nav>";
}


//register_activation_hook( __FILE__, 'learnx_notification_activation' );
function learnx_notification_activation($titre_notification) {


	
//création table job_learnx_notification_log
    
	global $wpdb, $table_prefix, $current_user;

    $resultat_Create = $wpdb->query("CREATE TABLE IF NOT EXISTS `".$table_prefix."learnx_notification_log` (
                              `id` INT NOT NULL AUTO_INCREMENT,
                              `notification` VARCHAR(255),
                              `date` TIMESTAMP,
                              `destinataires` VARCHAR(255),
                              PRIMARY KEY (`id`));");
	
	if(!$resultat_Create){

		echo "La table n'a pas pu être crée. ";
		
	}else{

		echo "La table a été crée ";

	}
	
//Test pour affichier le nom de l'utilisateur et le format de la date actuelle ("Y-m-d") et l'heure ("H:i:s") 

	//$user = get_current_user();

	//echo "| Le nom d'utilisateur est :".$current_user->user_login;
	
	
	//$Datetoday = date("Y-m-d")." à ".date("H:i:s"); 
	
	//echo " | La date du jour est ".$Datetoday;

//Affiche une erreur si la requête sélect echoue

//---------------------if(!empty($wpbd->last_error)){ 
		//echo $wpbd->last_error;
	

// Pour relever une erreur : $wpbd->last_error

	//exit();

// 
	/*
	$resultat_Create = $wpdb->query("DROP TABLE IF EXISTS `".$table_prefix."learnx_notification_log`;");

	if(!$resultat_Create){

		echo "La table n'a pas été supprimé ";
		
	}else{

		echo "La table a été supprimé ";

	}
	*/
	

		//exit();
	
//Insertion de données dans la table job_learnx_notification_log ET Vérification

	
	$resultat_insert = $wpdb->query("SET time_zone = '+01:00'");
	if(!$resultat_insert){

		echo "Time zone ok!";

	}else{
		echo "Time zone ko!";
}



	$resultat_insert = $wpdb->query("INSERT INTO `".$table_prefix."learnx_notification_log` (`notification`, `date`, `destinataires`)
		VALUES
		('".$titre_notification."', CURRENT_TIMESTAMP, '".$current_user->user_login."');"
		);
		/*
		$resultat_insert = $wpdb->query("INSERT INTO `".$table_prefix."learnx_notification_log` (`notification`, `date`, `destinataires`)
		VALUES
		('".$titre_notification."', '".$Datetoday."', '".$current_user->user_login."');"
		);
		*/
	if(!$resultat_insert){

		echo "Les données n'ont pas été insérées!";

	}else{
		echo "Les données ont bien été insérées!";
	}
/*----------------------
}else{
	echo " | Il n'y a pas d'erreur pour le moment";
}
-----------------------*/
/*
	$result = mysql_query('SELECT * WHERE 1=1');
	if (!$result) {
    die('Requête invalide : ' . mysql_error());
	}
*/

	//exit();
	
}
/*

//Sélection de toutes les données de la table job_learnx_notification_log et affichage dans un tableau

$resultat_select = $wpdb->get_results("SELECT * FROM `".$table_prefix."learnx_notification_log`");


//print_r($resultat_select);

echo "<table border=1>";
	echo "<tr>";
		echo "<th>Notification</th>";
		echo "<th>Date</th>";
		echo "<th>Destinataires</th>";
	echo "</tr>";
foreach($resultat_select as $result)
{
echo "<tr>";
	   echo "<td>".$result->notification."</td>";
	   echo "<td>".$result->date."</td>";
	   echo "<td>".$result->destinataires."</td>";
   echo "</tr>";
}
echo "</table>";

*/