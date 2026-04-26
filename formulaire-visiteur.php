<?php
/**
 * Plugin Name: Visitor Form
 * Description: Multi-step form for visitor registration
 * Version: 1.4.0
 * Text Domain: formulaire-visiteur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Formulaire_Visiteur {

    // Fixed API key for authenticated backend calls
    private const API_BASE       = 'http://172.16.0.1:8000/api/v1';
    private const API_LOGIN      = 'votre_login';
    private const API_PASSWORD   = 'votre_mot_de_passe';

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('formulaire_visiteur', array($this, 'render_form'));

        add_action('wp_ajax_save_visitor',          array($this, 'save_visitor'));
        add_action('wp_ajax_nopriv_save_visitor',   array($this, 'save_visitor'));
        add_action('wp_ajax_check_email',           array($this, 'check_email'));
        add_action('wp_ajax_nopriv_check_email',    array($this, 'check_email'));
        add_action('wp_ajax_get_visitor',           array($this, 'get_visitor'));
        add_action('wp_ajax_nopriv_get_visitor',    array($this, 'get_visitor'));
        add_action('wp_ajax_update_visitor',        array($this, 'update_visitor'));
        add_action('wp_ajax_nopriv_update_visitor', array($this, 'update_visitor'));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'create_table'));
        register_activation_hook(__FILE__, array($this, 'create_table'));
    }

    // -------------------------------------------------------
    //  Helper: backend API call
    // -------------------------------------------------------
    private function api_request(string $method, string $endpoint, array $body = [], string $visitor_token = '') {
        $token = $this->get_bearer_token();

        $headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        );

        if (!empty($visitor_token)) {
            $headers['X-Visitor-Token'] = $visitor_token;
        }

        $args = array(
            'method'   => strtoupper($method),
            'timeout'  => 15,
            'blocking' => true,
            'headers'  => $headers,
        );

        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        return wp_remote_request(self::API_BASE . $endpoint, $args);
    }

    // -------------------------------------------------------
    //  Local database
    // -------------------------------------------------------
    public function create_table() {
        global $wpdb;
        $table_name      = $wpdb->prefix . 'visiteurs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            civilite varchar(10) NOT NULL,
            nom varchar(100) NOT NULL,
            prenom varchar(100) NOT NULL,
            email varchar(100) NOT NULL DEFAULT '',
            numero_telephone varchar(20),
            postal_code varchar(10) NOT NULL DEFAULT '',
            city varchar(100) NOT NULL DEFAULT '',
            nom_objet varchar(100) NOT NULL DEFAULT '',
            marque varchar(100) NOT NULL DEFAULT '',
            age_objet varchar(20) NOT NULL DEFAULT '',
            poids_objet varchar(20) NOT NULL DEFAULT '',
            est_electrique varchar(3) NOT NULL,
            description_probleme text NOT NULL,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // -------------------------------------------------------
    //  Scripts
    // -------------------------------------------------------
    public function enqueue_scripts() {
        wp_enqueue_style('formulaire-visiteur-css', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('formulaire-visiteur-js', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.4.0', true);
        wp_localize_script('formulaire-visiteur-js', 'formData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('visitor_nonce'),
        ));
    }

    // -------------------------------------------------------
    //  HTML Form
    // -------------------------------------------------------
    public function render_form() {
        ob_start();
        ?>
        <div class="formulaire-visiteur-container">

            <!-- ===== REGISTRATION MODE ===== -->
            <div id="mode-inscription">

                <div class="form-card active" data-step="1">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <label class="form-label">Civilitty:</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="civilite" value="Mme" required><span>Mme</span></label>
                            <label class="radio-label"><input type="radio" name="civilite" value="Mr" required><span>Mr</span></label>
                            <label class="radio-label"><input type="radio" name="civilite" value="Autre" required><span>Autre</span></label>
                        </div>
                    </div>
                    <button class="btn-suivant" onclick="nextStep(2)">Next</button>
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
                        <button class="btn-retour" onclick="prevStep(1)">Back</button>
                        <button class="btn-suivant" onclick="nextStep(3)">Next</button>
                    </div>
                </div>

                <div class="form-card" data-step="3">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <div class="form-group">
                            <label class="form-label">Email address:</label>
                            <input type="email" id="email" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone number (optional if email provided):</label>
                            <input type="text" id="numero_telephone" class="form-input" autocomplete="off">
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="prevStep(2)">Back</button>
                        <button class="btn-suivant" onclick="nextStep(4)">Next</button>
                    </div>
                </div>

                <div class="form-card" data-step="4">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <div class="form-group">
                            <label class="form-label">Code postal :</label>
                            <input type="text" id="postal_code" class="form-input" placeholder="Ex: 37000" required autocomplete="off">
                            <div id="postalSuggestions" class="postal-suggestions"></div>
                            <label class="form-label">Ville :</label>
                            <input type="text" id="city" class="form-input" placeholder="Ex: Tours" required autocomplete="off">
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="prevStep(3)">Back</button>
                        <button class="btn-suivant" onclick="nextStep(5)">Next</button>
                    </div>
                </div>

                <div class="form-card" data-step="5">
                    <h2 class="form-title">Formulaire Visiteur</h2>
                    <div class="form-content">
                        <label class="form-label">Is the object electrical?:</label>
                        <div class="radio-group">
                            <label class="radio-label"><input type="radio" name="electrique" value="oui" required><span>Oui</span></label>
                            <label class="radio-label"><input type="radio" name="electrique" value="non" required><span>Non</span></label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom de l'objet :</label>
                            <input type="text" id="nom_objet" class="form-input" placeholder="Ex: Lave-linge" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marque :</label>
                            <input type="text" id="marque" class="form-input" placeholder="Ex: Samsung" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Âge de l'objet :</label>
                            <input type="text" id="age_objet" class="form-input" placeholder="Ex: 5 ans" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Poids de l'objet :</label>
                            <input type="text" id="poids_objet" class="form-input" placeholder="Ex: 2 kg" autocomplete="off">
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
                        <div class="form-group">
                            <label class="form-label">Description du problème :</label>
                            <textarea id="description_probleme" class="form-input" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="prevStep(5)">Retour</button>
                        <button class="btn-suivant" onclick="submitForm()">Envoyer</button>
                    </div>
                </div>

                <div class="form-message" id="formMessage"></div>

            </div><!-- /mode-inscription -->

            <!-- ===== EDIT MODE (displayed if ?visitor=X&token=Y in URL) ===== -->
            <div id="mode-modification" style="display:none;">

                <!-- Loading -->
                <div class="form-card active" data-edit-step="loading">
                    <h2 class="form-title">Chargement de votre fiche...</h2>
                    <div class="form-content">
                        <p class="form-intro">Veuillez patienter.</p>
                    </div>
                </div>

                <!-- Invalid token error -->
                <div class="form-card" data-edit-step="error">
                    <h2 class="form-title">Lien invalide</h2>
                    <div class="form-content">
                        <p class="form-intro" id="editErrorMessage">Ce lien est invalide ou a expiré. Veuillez contacter l'organisateur pour obtenir un nouveau lien.</p>
                    </div>
                </div>

                <!-- Edit form -->
                <div class="form-card" data-edit-step="form">
                    <h2 class="form-title">Modifier mes informations</h2>
                    <input type="hidden" id="edit_visitor_id">
                    <input type="hidden" id="edit_visitor_token">
                    <input type="hidden" id="edit_item_id">
                    <div class="form-content">
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
                            <div class="form-group">
                                <label class="form-label">Code postal :</label>
                                <input type="text" id="edit_postal_code" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ville :</label>
                                <input type="text" id="edit_city" class="form-input">
                            </div>
                        </fieldset>
                        <fieldset class="edit-section">
                            <legend>Objet</legend>
                            <div class="form-group">
                                <label class="form-label">Nom de l'objet :</label>
                                <input type="text" id="edit_nom_objet" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Marque :</label>
                                <input type="text" id="edit_marque" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Âge de l'objet :</label>
                                <input type="text" id="edit_age_objet" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Poids de l'objet :</label>
                                <input type="text" id="edit_poids_objet" class="form-input">
                            </div>
                        </fieldset>
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
                        <button class="btn-suivant" onclick="updateVisitor()">Enregistrer les modifications</button>
                    </div>
                </div>

                <!-- Edit success -->
                <div class="form-card" data-edit-step="success">
                    <h2 class="form-title">Modifications enregistrées</h2>
                    <div class="form-content">
                        <p class="form-intro">Vos informations ont bien été mises à jour. Merci !</p>
                    </div>
                </div>

            </div><!-- /mode-modification -->

        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------
    //  AJAX: retrieve visitor data via URL token
    // -------------------------------------------------------
    public function get_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        $visitor_id    = intval($_POST['visitor_id']    ?? 0);
        $visitor_token = sanitize_text_field($_POST['visitor_token'] ?? '');

        if (!$visitor_id || empty($visitor_token)) {
            wp_send_json_error(array('message' => 'Paramètres manquants.'));
            wp_die();
        }

        $response = $this->api_request('GET', '/visitors/' . $visitor_id, [], $visitor_token);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Erreur réseau.'));
            wp_die();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);

        if ($status === 401 || $status === 403) {
            wp_send_json_error(array('message' => 'Lien invalide ou expiré.'));
            wp_die();
        }

        if ($status !== 200) {
            wp_send_json_error(array('message' => 'Visiteur introuvable.'));
            wp_die();
        }

        // Retrieve first item of visitor
        $item = null;
        $item_response = $this->api_request('GET', '/visitors/' . $visitor_id . '/items', [], $visitor_token);
        if (!is_wp_error($item_response) && wp_remote_retrieve_response_code($item_response) === 200) {
            $items = json_decode(wp_remote_retrieve_body($item_response), true);
            $item  = (is_array($items) && count($items) > 0) ? $items[0] : null;
        }

        wp_send_json_success(array(
            'visitor' => $body,
            'item'    => $item,
        ));
        wp_die();
    }

    // -------------------------------------------------------
    //  AJAX: update visitor via URL token
    // -------------------------------------------------------
    public function update_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        $visitor_id    = intval($_POST['visitor_id']    ?? 0);
        $visitor_token = sanitize_text_field($_POST['visitor_token'] ?? '');

        if (!$visitor_id || empty($visitor_token)) {
            wp_send_json_error(array('message' => 'Paramètres manquants.'));
            wp_die();
        }

        $data_visitor = array(
            'title'         => sanitize_text_field($_POST['civilite']   ?? ''),
            'name'          => sanitize_text_field(strtoupper($_POST['nom'] ?? '')),
            'surname'       => sanitize_text_field($_POST['prenom']     ?? ''),
            'zip_code'      => sanitize_text_field($_POST['postal_code'] ?? ''),
            'city'          => sanitize_text_field($_POST['city']       ?? ''),
            'notification'  => false,
            'visitor_token' => $visitor_token,
        );

        $response = $this->api_request('PATCH', '/visitors/' . $visitor_id, $data_visitor, $visitor_token);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Erreur réseau visiteur.')); wp_die();
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status === 401 || $status === 403) {
            wp_send_json_error(array('message' => 'Lien invalide ou expiré.')); wp_die();
        }
        if ($status < 200 || $status >= 300) {
            wp_send_json_error(array('message' => "Erreur mise à jour visiteur (code $status).")); wp_die();
        }

        // Update item if item_id provided
        $item_id = intval($_POST['item_id'] ?? 0);
        if ($item_id) {
            $data_item = array(
                'name'        => sanitize_text_field($_POST['nom_objet']   ?? ''),
                'brand'       => sanitize_text_field($_POST['marque']      ?? ''),
                'age'         => sanitize_text_field($_POST['age_objet']   ?? ''),
                'weight'      => sanitize_text_field($_POST['poids_objet'] ?? ''),
                'is_electric' => ($_POST['est_electrique'] ?? '') === 'oui',
            );

            $response_item = $this->api_request('PATCH', '/items/' . $item_id, $data_item, $visitor_token);

            if (is_wp_error($response_item)) {
                wp_send_json_error(array('message' => 'Erreur réseau item.')); wp_die();
            }

            $status_item = wp_remote_retrieve_response_code($response_item);
            if ($status_item < 200 || $status_item >= 300) {
                wp_send_json_error(array('message' => "Erreur mise à jour item (code $status_item).")); wp_die();
            }
        }

        wp_send_json_success(array('message' => 'Modifications enregistrées avec succès !'));
        wp_die();
    }

    // -------------------------------------------------------
    //  AJAX: register new visitor
    // -------------------------------------------------------
    public function save_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $email     = !empty($_POST['email'])            ? sanitize_email($_POST['email'])                : '';
        $telephone = !empty($_POST['numero_telephone']) ? sanitize_text_field($_POST['numero_telephone']) : '';

        if (empty($email) && empty($telephone)) {
            wp_send_json_error(array('message' => 'Veuillez fournir au moins une adresse email ou un numéro de téléphone.'));
            wp_die();
        }

        if (!empty($email)) {
            $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s LIMIT 1", $email));
            if ($existing) {
                wp_send_json_error(array('field' => 'email', 'message' => 'Cette adresse email est déjà utilisée.'));
                wp_die();
            }
        }

        // Local save
        $wpdb->insert($table_name, array(
            'civilite'             => sanitize_text_field($_POST['civilite']),
            'nom'                  => sanitize_text_field(strtoupper($_POST['nom'])),
            'prenom'               => sanitize_text_field($_POST['prenom']),
            'email'                => $email,
            'numero_telephone'     => $telephone,
            'postal_code'          => sanitize_text_field($_POST['postal_code']),
            'city'                 => !empty($_POST['city']) ? sanitize_text_field($_POST['city']) : 'Non renseignée',
            'est_electrique'       => sanitize_text_field($_POST['est_electrique']),
            'nom_objet'            => sanitize_text_field($_POST['nom_objet']   ?? ''),
            'marque'               => sanitize_text_field($_POST['marque']      ?? ''),
            'age_objet'            => sanitize_text_field($_POST['age_objet']   ?? ''),
            'poids_objet'          => sanitize_text_field($_POST['poids_objet'] ?? ''),
            'description_probleme' => sanitize_textarea_field($_POST['description_probleme']),
        ));

        // Send visitor to backend
        $data_backend = array(
            'title'        => sanitize_text_field($_POST['civilite']),
            'name'         => sanitize_text_field(strtoupper($_POST['nom'])),
            'surname'      => sanitize_text_field($_POST['prenom']),
            'city'         => !empty($_POST['city']) ? sanitize_text_field($_POST['city']) : 'Non renseignée',
            'phone_number' => $telephone,
            'source'       => 'wordpress_form',
            'zip_code'     => sanitize_text_field($_POST['postal_code']),
            'email'        => $email,
        );

        $response_visitor = $this->api_request('POST', '/visitors', $data_backend);

        if (is_wp_error($response_visitor)) {
            wp_send_json_error(array('message' => 'Erreur réseau visiteur.')); wp_die();
        }

        $status_visitor = wp_remote_retrieve_response_code($response_visitor);
        $body_visitor   = wp_remote_retrieve_body($response_visitor);

        if ($status_visitor < 200 || $status_visitor >= 300) {
            wp_send_json_error(array('message' => "Backend visiteur refusé (code $status_visitor).")); wp_die();
        }

        $backend_visitor_id = json_decode($body_visitor, true)['id'] ?? null;
        if (!$backend_visitor_id) {
            wp_send_json_error(array('message' => 'ID visiteur backend introuvable.')); wp_die();
        }

        // Send item to backend
        $data_item = array(
            'weight'      => sanitize_text_field($_POST['poids_objet'] ?? ''),
            'age'         => sanitize_text_field($_POST['age_objet']   ?? ''),
            'name'        => sanitize_text_field($_POST['nom_objet']   ?? ''),
            'is_electric' => sanitize_text_field($_POST['est_electrique']) === 'oui',
            'brand'       => sanitize_text_field($_POST['marque']      ?? ''),
        );

        $response_item = $this->api_request('POST', '/visitors/' . $backend_visitor_id . '/items', $data_item);
        $status_item   = wp_remote_retrieve_response_code($response_item);

        if (is_wp_error($response_item) || $status_item < 200 || $status_item >= 300) {
            wp_send_json_error(array('message' => "Backend item refusé (code $status_item).")); wp_die();
        }

        wp_send_json_success(array('message' => 'Visiteur enregistré avec succès !'));
        wp_die();
    }

    private function get_bearer_token(): string {
        $cached = get_transient('fv_bearer_token');
        if ($cached) return $cached;

        $response = wp_remote_post(self::API_BASE . '/auth/login', array(
            'method'  => 'POST',
            'timeout' => 15,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body'    => json_encode(array('login' => self::API_LOGIN, 'password' => self::API_PASSWORD)),
        ));

        if (is_wp_error($response)) return '';

        $body  = json_decode(wp_remote_retrieve_body($response), true);
        $token = $body['token'] ?? '';

        if ($token) {
            // Cache the token 8h (Sanctum tokens are usually long-lived)
            set_transient('fv_bearer_token', $token, 8 * HOUR_IN_SECONDS);
        }

        return $token;
    }

    // -------------------------------------------------------
    //  AJAX: check email availability
    // -------------------------------------------------------
    public function check_email() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        $email      = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email)) { wp_send_json_success(array('available' => true)); wp_die(); }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s LIMIT 1", $email));
        if ($existing) {
            wp_send_json_error(array('message' => 'Cette adresse email est déjà utilisée.'));
        } else {
            wp_send_json_success(array('available' => true));
        }
        wp_die();
    }

    // -------------------------------------------------------
    //  Admin
    // -------------------------------------------------------
    public function add_admin_menu() {
        add_menu_page('Visiteurs', 'Visiteurs', 'manage_options', 'formulaire-visiteurs', array($this, 'admin_page'), 'dashicons-id', 25);
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        $visiteurs  = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_creation DESC");
        ?>
        <div class="wrap">
            <h1>Liste des Visiteurs</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr>
                    <th>ID</th><th>Civilité</th><th>Nom</th><th>Prénom</th>
                    <th>Email</th><th>Téléphone</th><th>CP</th><th>Ville</th>
                    <th>Électrique</th><th>Objet</th><th>Marque</th><th>Âge</th><th>Poids</th>
                    <th>Description</th><th>Date</th>
                </tr></thead>
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
                        <td><?php echo esc_html($v->city); ?></td>
                        <td><?php echo esc_html($v->est_electrique); ?></td>
                        <td><?php echo esc_html($v->nom_objet); ?></td>
                        <td><?php echo esc_html($v->marque); ?></td>
                        <td><?php echo esc_html($v->age_objet); ?></td>
                        <td><?php echo esc_html($v->poids_objet); ?></td>
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