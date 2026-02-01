<?php
/**
 * Plugin Name: Formulaire Visiteur
 * Plugin URI: https://example.com
 * Description: Formulaire multi-étapes pour l'enregistrement des visiteurs
 * Version: 1.0.0
 * Author: Votre Nom
 * Text Domain: formulaire-visiteur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Formulaire_Visiteur {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('formulaire_visiteur', array($this, 'render_form'));
        add_action('wp_ajax_save_visitor', array($this, 'save_visitor'));
        add_action('wp_ajax_nopriv_save_visitor', array($this, 'save_visitor'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'create_table'));
    }
    
    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            civilite varchar(10) NOT NULL,
            nom varchar(100) NOT NULL,
            prenom varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            objet varchar(255) NOT NULL,
            est_electrique varchar(3) NOT NULL,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('formulaire-visiteur-css', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('formulaire-visiteur-js', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('formulaire-visiteur-js', 'formData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('visitor_nonce')
        ));
    }
    
    public function render_form() {
        ob_start();
        ?>
        <div class="formulaire-visiteur-container">
            <div class="form-card active" data-step="1">
                <h2 class="form-title">Formulaire Visiteur</h2>
                <div class="form-content">
                    <label class="form-label">Civilitée :</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="civilite" value="Mme" required>
                            <span>Mme</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="civilite" value="Mr" required>
                            <span>Mr</span>
                        </label>
                    </div>
                </div>
                <button class="btn-suivant" onclick="nextStep(2)">Suivant</button>
            </div>

            <div class="form-card" data-step="2">
                <h2 class="form-title">Formulaire Visiteur</h2>
                <div class="form-content">
                    <div class="form-group">
                        <label class="form-label">Nom (en MAJUSCULE) :</label>
                        <input type="text" id="nom" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénom :</label>
                        <input type="text" id="prenom" class="form-input" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn-retour" onclick="prevStep(1)">Retour</button>
                    <button class="btn-suivant" onclick="nextStep(3)">Suivant</button>
                </div>
            </div>

            <div class="form-card" data-step="3">
                <h2 class="form-title">Formulaire Visiteur</h2>
                <div class="form-content">
                    <div class="form-group">
                        <label class="form-label">Adresse email:</label>
                        <input type="email" id="email" class="form-input" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn-retour" onclick="prevStep(2)">Retour</button>
                    <button class="btn-suivant" onclick="nextStep(4)">Suivant</button>
                </div>
            </div>

            <div class="form-card" data-step="4">
                <h2 class="form-title">Formulaire Visiteur</h2>
                <div class="form-content">
                    <div class="form-group">
                        <label class="form-label">Objet :</label>
                        <input type="text" id="objet" class="form-input" required>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn-retour" onclick="prevStep(3)">Retour</button>
                    <button class="btn-suivant" onclick="nextStep(5)">Suivant</button>
                </div>
            </div>

            <div class="form-card" data-step="5">
                <h2 class="form-title">Formulaire Visiteur</h2>
                <div class="form-content">
                    <label class="form-label">L'objet est-il électrique ? :</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="electrique" value="oui" required>
                            <span>oui</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="electrique" value="non" required>
                            <span>non</span>
                        </label>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn-retour" onclick="prevStep(4)">Retour</button>
                    <button class="btn-suivant" onclick="submitForm()">Suivant</button>
                </div>
            </div>

            <div class="form-message" id="formMessage"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function save_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        
        $data = array(
            'civilite' => sanitize_text_field($_POST['civilite']),
            'nom' => sanitize_text_field(strtoupper($_POST['nom'])),
            'prenom' => sanitize_text_field($_POST['prenom']),
            'email' => sanitize_email($_POST['email']),
            'objet' => sanitize_text_field($_POST['objet']),
            'est_electrique' => sanitize_text_field($_POST['est_electrique'])
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Formulaire soumis avec succès !'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement.'));
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Visiteurs',
            'Visiteurs',
            'manage_options',
            'formulaire-visiteurs',
            array($this, 'admin_page'),
            'dashicons-id',
            25
        );
    }
    
    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        $visiteurs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_creation DESC");
        ?>
        <div class="wrap">
            <h1>Liste des Visiteurs</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Civilité</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Objet</th>
                        <th>Électrique</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visiteurs as $visiteur): ?>
                    <tr>
                        <td><?php echo esc_html($visiteur->id); ?></td>
                        <td><?php echo esc_html($visiteur->civilite); ?></td>
                        <td><?php echo esc_html($visiteur->nom); ?></td>
                        <td><?php echo esc_html($visiteur->prenom); ?></td>
                        <td><?php echo esc_html($visiteur->email); ?></td>
                        <td><?php echo esc_html($visiteur->objet); ?></td>
                        <td><?php echo esc_html($visiteur->est_electrique); ?></td>
                        <td><?php echo esc_html($visiteur->date_creation); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new Formulaire_Visiteur();