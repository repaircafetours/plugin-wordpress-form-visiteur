<?php
/**
 * Plugin Name: Formulaire Visiteur
 * Plugin URI: https://example.com
 * Description: Formulaire multi-étapes pour l'enregistrement des visiteurs
 * Version: 1.1.0
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
        add_action('wp_ajax_find_visitor', array($this, 'find_visitor'));
        add_action('wp_ajax_nopriv_find_visitor', array($this, 'find_visitor'));
        add_action('wp_ajax_update_visitor', array($this, 'update_visitor'));
        add_action('wp_ajax_nopriv_update_visitor', array($this, 'update_visitor'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'create_table'));
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
            email varchar(100),
            numero_telephone varchar(20),
            objet varchar(255) NOT NULL,
            postal_code varchar(10) NOT NULL DEFAULT '',
            est_electrique varchar(3) NOT NULL,
            description_probleme text NOT NULL,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('formulaire-visiteur-css', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('formulaire-visiteur-js', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.1.0', true);
        wp_localize_script('formulaire-visiteur-js', 'formData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('visitor_nonce')
        ));
    }
    
    public function render_form() {
        ob_start();
        ?>
        <div class="formulaire-visiteur-container">

            <!-- ===================== -->
            <!--  MODE : INSCRIPTION   -->
            <!-- ===================== -->
            <div id="mode-inscription">

                <div class="form-card active" data-step="1">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <label class="form-label">Civilitée :</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="civilite" value="Mme" required><span>Mme</span></label>
                            <label class="radio-label"><input type="radio" name="civilite" value="Mr" required><span>Mr</span></label>
                            <label class="radio-label"><input type="radio" name="civilite" value="Autre" required><span>Autre</span></label>
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
                            <label class="form-label">Adresse email :</label>
                            <input type="email" id="email" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Numéro de téléphone (optionnel si email fourni) :</label>
                            <input type="text" id="numero_telephone" class="form-input" autocomplete="off">
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
                            <label class="form-label">Catégorie d'objet :</label>
                            <select id="objet" class="form-input" required>
                                <option value="">-- Sélectionnez une catégorie --</option>
                                <option value="électroménagé">Électroménagé</option>
                                <option value="vêtement">Vêtement</option>
                                <option value="informatique">Informatique</option>
                            </select>
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
                        <div class="form-group">
                            <label class="form-label">Code postal :</label>
                            <input type="text" id="postal_code" class="form-input" placeholder="Ex: 37000" required autocomplete="off">
                            <div id="postalSuggestions" class="postal-suggestions"></div>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="prevStep(4)">Retour</button>
                        <button class="btn-suivant" onclick="nextStep(6)">Suivant</button>
                    </div>
                </div>

                <div class="form-card" data-step="6">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <label class="form-label">L'objet est-il électrique ? :</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="electrique" value="oui" required><span>oui</span></label>
                            <label class="radio-label"><input type="radio" name="electrique" value="non" required><span>non</span></label>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="prevStep(5)">Retour</button>
                        <button class="btn-suivant" onclick="nextStep(7)">Suivant</button>
                    </div>
                </div>

                <div class="form-card" data-step="7">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <div class="form-group">
                            <label class="form-label">Description du problème :</label>
                            <textarea id="description_probleme" class="form-input" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="prevStep(6)">Retour</button>
                        <button class="btn-suivant" onclick="submitForm()">Envoyer</button>
                    </div>
                </div>

                <div class="form-message" id="formMessage"></div>

                <!-- Lien vers le mode modification -->
                <div class="mode-switch">
                    <p>Déjà inscrit ? <a href="#" id="switchToEdit">Modifier mes informations</a></p>
                </div>

            </div><!-- /mode-inscription -->


            <!-- ===================== -->
            <!--  MODE : MODIFICATION  -->
            <!-- ===================== -->
            <div id="mode-modification" style="display:none;">

                <!-- Étape M1 : Recherche du visiteur -->
                <div class="form-card active" data-edit-step="1">
                    <h2 class="form-title">Modifier mes informations</h2>
                    <div class="form-content">
                        <p class="form-intro">Entrez votre email ou votre numéro de téléphone pour retrouver votre fiche.</p>
                        <div class="form-group">
                            <label class="form-label">Adresse email :</label>
                            <input type="email" id="edit_email_search" class="form-input" placeholder="votre@email.com">
                        </div>
                        <p class="form-ou">— ou —</p>
                        <div class="form-group">
                            <label class="form-label">Numéro de téléphone :</label>
                            <input type="text" id="edit_phone_search" class="form-input" placeholder="06 00 00 00 00">
                        </div>
                    </div>
                    <div class="form-message" id="editSearchMessage"></div>
                    <div class="form-buttons">
                        <button class="btn-retour" id="switchToRegister">Retour</button>
                        <button class="btn-suivant" onclick="findVisitor()">Rechercher</button>
                    </div>
                </div>

                <!-- Étape M2 : Formulaire de modification -->
                <div class="form-card" data-edit-step="2">
                    <h2 class="form-title">Modifier mes informations</h2>
                    <input type="hidden" id="edit_visitor_id">

                    <div class="form-content">

                        <!-- Civilité / Nom / Prénom -->
                        <fieldset class="edit-section">
                            <legend>Identité</legend>
                            <label class="form-label">Civilité :</label>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="edit_civilite" value="Mme"><span>Mme</span></label>
                                <label class="radio-label"><input type="radio" name="edit_civilite" value="Mr"><span>Mr</span></label>
                                <label class="radio-label"><input type="radio" name="edit_civilite" value="Autre"><span>Autre</span></label>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nom (en MAJUSCULE) :</label>
                                <input type="text" id="edit_nom" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Prénom :</label>
                                <input type="text" id="edit_prenom" class="form-input">
                            </div>
                        </fieldset>

                        <!-- Catégorie / Code postal -->
                        <fieldset class="edit-section">
                            <legend>Objet</legend>
                            <div class="form-group">
                                <label class="form-label">Catégorie d'objet :</label>
                                <select id="edit_objet" class="form-input">
                                    <option value="">-- Sélectionnez une catégorie --</option>
                                    <option value="électroménagé">Électroménagé</option>
                                    <option value="vêtement">Vêtement</option>
                                    <option value="informatique">Informatique</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Code postal :</label>
                                <input type="text" id="edit_postal_code" class="form-input">
                            </div>
                        </fieldset>

                        <!-- Description du problème -->
                        <fieldset class="edit-section">
                            <legend>Problème</legend>
                            <div class="form-group">
                                <label class="form-label">Description du problème :</label>
                                <textarea id="edit_description_probleme" class="form-input" rows="5"></textarea>
                            </div>
                        </fieldset>

                    </div>

                    <div class="form-message" id="editUpdateMessage"></div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="backToEditSearch()">Retour</button>
                        <button class="btn-suivant" onclick="updateVisitor()">Enregistrer les modifications</button>
                    </div>
                </div>

            </div><!-- /mode-modification -->

        </div><!-- /formulaire-visiteur-container -->
        <?php
        return ob_get_clean();
    }

    public function save_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        
        $email     = !empty($_POST['email'])            ? sanitize_email($_POST['email'])            : '';
        $telephone = !empty($_POST['numero_telephone']) ? sanitize_text_field($_POST['numero_telephone']) : '';
        
        if (empty($email) && empty($telephone)) {
            wp_send_json_error(array('message' => 'Veuillez fournir au moins une adresse email ou un numéro de téléphone.'));
            wp_die();
        }
        
        $data = array(
            'civilite'             => sanitize_text_field($_POST['civilite']),
            'nom'                  => sanitize_text_field(strtoupper($_POST['nom'])),
            'prenom'               => sanitize_text_field($_POST['prenom']),
            'email'                => $email,
            'numero_telephone'     => $telephone,
            'objet'                => sanitize_text_field($_POST['objet']),
            'postal_code'          => sanitize_text_field($_POST['postal_code']),
            'est_electrique'       => sanitize_text_field($_POST['est_electrique']),
            'description_probleme' => sanitize_textarea_field($_POST['description_probleme']),
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Formulaire soumis avec succès !'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement.'));
        }
    }

    // -------------------------------------------------------
    // Recherche du visiteur par email ou téléphone
    // -------------------------------------------------------
    public function find_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $email     = !empty($_POST['email'])  ? sanitize_email($_POST['email'])            : '';
        $telephone = !empty($_POST['phone'])  ? sanitize_text_field($_POST['phone'])       : '';

        if (empty($email) && empty($telephone)) {
            wp_send_json_error(array('message' => 'Veuillez fournir un email ou un numéro de téléphone.'));
            wp_die();
        }

        $visiteur = null;
        if (!empty($email)) {
            $visiteur = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s ORDER BY date_creation DESC LIMIT 1", $email)
            );
        }
        if (!$visiteur && !empty($telephone)) {
            $visiteur = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_name WHERE numero_telephone = %s ORDER BY date_creation DESC LIMIT 1", $telephone)
            );
        }

        if (!$visiteur) {
            wp_send_json_error(array('message' => 'Aucun visiteur trouvé avec ces informations.'));
            wp_die();
        }

        wp_send_json_success(array(
            'id'                   => $visiteur->id,
            'civilite'             => $visiteur->civilite,
            'nom'                  => $visiteur->nom,
            'prenom'               => $visiteur->prenom,
            'objet'                => $visiteur->objet,
            'postal_code'          => $visiteur->postal_code,
            'description_probleme' => $visiteur->description_probleme,
        ));
    }

    // -------------------------------------------------------
    //  Mise à jour du visiteur
    // -------------------------------------------------------
    public function update_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $id = intval($_POST['id']);
        if (!$id) {
            wp_send_json_error(array('message' => 'Identifiant invalide.'));
            wp_die();
        }

        // Vérifier que le visiteur existe
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $id));
        if (!$exists) {
            wp_send_json_error(array('message' => 'Visiteur introuvable.'));
            wp_die();
        }

        $data = array(
            'civilite'             => sanitize_text_field($_POST['civilite']),
            'nom'                  => sanitize_text_field(strtoupper($_POST['nom'])),
            'prenom'               => sanitize_text_field($_POST['prenom']),
            'objet'                => sanitize_text_field($_POST['objet']),
            'postal_code'          => sanitize_text_field($_POST['postal_code']),
            'description_probleme' => sanitize_textarea_field($_POST['description_probleme']),
        );

        $result = $wpdb->update($table_name, $data, array('id' => $id));

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Vos informations ont été mises à jour avec succès !'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la mise à jour.'));
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Visiteurs', 'Visiteurs', 'manage_options',
            'formulaire-visiteurs', array($this, 'admin_page'),
            'dashicons-id', 25
        );
    }
    
    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        $visiteurs  = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_creation DESC");
        ?>
        <div class="wrap">
            <h1>Liste des Visiteurs</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Civilité</th><th>Nom</th><th>Prénom</th>
                        <th>Email</th><th>Téléphone</th><th>Code postal</th>
                        <th>Catégorie</th><th>Électrique</th><th>Description Problème</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visiteurs as $v): ?>
                    <tr>
                        <td><?php echo esc_html($v->id); ?></td>
                        <td><?php echo esc_html($v->civilite); ?></td>
                        <td><?php echo esc_html($v->nom); ?></td>
                        <td><?php echo esc_html($v->prenom); ?></td>
                        <td><?php echo esc_html($v->email); ?></td>
                        <td><?php echo esc_html($v->numero_telephone); ?></td>
                        <td><?php echo esc_html($v->postal_code); ?></td>
                        <td><?php echo esc_html($v->objet); ?></td>
                        <td><?php echo esc_html($v->est_electrique); ?></td>
                        <td><?php echo esc_html($v->description_probleme); ?></td>
                        <td><?php echo esc_html($v->date_creation); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new Formulaire_Visiteur();