<?php
/**
 * Plugin Name: Visitor Form
 * Description: Multi-step form for visitor registration
 * Version: 1.3.0
 * Text Domain: formulaire-visiteur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Formulaire_Visiteur {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('formulaire_visiteur', array($this, 'render_form'));
        add_action('wp_ajax_save_visitor',              array($this, 'save_visitor'));
        add_action('wp_ajax_nopriv_save_visitor',       array($this, 'save_visitor'));
        add_action('wp_ajax_find_visitor',              array($this, 'find_visitor'));
        add_action('wp_ajax_nopriv_find_visitor',       array($this, 'find_visitor'));
        add_action('wp_ajax_update_visitor',            array($this, 'update_visitor'));
        add_action('wp_ajax_nopriv_update_visitor',     array($this, 'update_visitor'));
        add_action('wp_ajax_check_email',               array($this, 'check_email'));
        add_action('wp_ajax_nopriv_check_email',        array($this, 'check_email'));
        add_action('wp_ajax_send_verification_token',   array($this, 'send_verification_token'));
        add_action('wp_ajax_nopriv_send_verification_token', array($this, 'send_verification_token'));
        add_action('wp_ajax_verify_token',              array($this, 'verify_token'));
        add_action('wp_ajax_nopriv_verify_token',       array($this, 'verify_token'));
        add_action('admin_menu',  array($this, 'add_admin_menu'));
        add_action('admin_init',  array($this, 'create_table'));
        register_activation_hook(__FILE__, array($this, 'create_table'));
    }

    // -------------------------------------------------------
    //  Database
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
            verification_token varchar(64) DEFAULT NULL,
            token_expires_at datetime DEFAULT NULL,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $wpdb->query("ALTER TABLE $table_name ADD COLUMN IF NOT EXISTS verification_token varchar(64) DEFAULT NULL");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN IF NOT EXISTS token_expires_at datetime DEFAULT NULL");
    }

    // -------------------------------------------------------
    //  Scripts
    // -------------------------------------------------------
    public function enqueue_scripts() {
        wp_enqueue_style('formulaire-visiteur-css', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('formulaire-visiteur-js', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.3.0', true);
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
                            <label class="form-label">Code postal :</label>
                            <input type="text" id="postal_code" class="form-input" placeholder="Ex: 37000" required autocomplete="off">
                            <div id="postalSuggestions" class="postal-suggestions"></div>
                            <label class="form-label">Ville :</label>
                            <input type="text" id="city" class="form-input" placeholder="Ex: Tours" required autocomplete="off">
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

                <div class="mode-switch">
                    <p>Déjà inscrit ? <a href="#" id="switchToEdit">Modifier mes informations</a></p>
                </div>

            </div><!-- /mode-inscription -->

            <div id="mode-modification" style="display:none;">

                <div class="form-card active" data-edit-step="1">
                    <h2 class="form-title">Modifier mes informations</h2>
                    <div class="form-content">
                        <p class="form-intro">Entrez votre email ou votre numéro de téléphone pour retrouver votre fiche. Un code de vérification vous sera envoyé par email.</p>
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

                <div class="form-card" data-edit-step="2">
                    <h2 class="form-title">Vérification de votre identité</h2>
                    <div class="form-content">
                        <p class="form-intro" id="tokenSentToEmail"></p>
                        <div class="form-group">
                            <label class="form-label">Code de vérification :</label>
                            <input type="text" id="edit_token_input" class="form-input" placeholder="Ex: 482951" maxlength="6" autocomplete="off">
                        </div>
                        <p class="form-intro" style="font-size:0.85em; color:#666;">
                            Le code est valable 15 minutes.<br>
                            <a href="#" id="resendToken">Renvoyer le code</a>
                        </p>
                    </div>
                    <div class="form-message" id="editTokenMessage"></div>
                    <div class="form-buttons">
                        <button class="btn-retour" onclick="backToEditSearchFromToken()">Retour</button>
                        <button class="btn-suivant" onclick="verifyToken()">Valider le code</button>
                    </div>
                </div>

                <div class="form-card" data-edit-step="3">
                    <h2 class="form-title">Modifier mes informations</h2>
                    <input type="hidden" id="edit_visitor_id">
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
                        <button class="btn-retour" onclick="backToEditSearch()">Retour</button>
                        <button class="btn-suivant" onclick="updateVisitor()">Enregistrer les modifications</button>
                    </div>
                </div>

            </div><!-- /mode-modification -->

        </div>
        <?php
        return ob_get_clean();
    }

    // -------------------------------------------------------
    //  Token: sending via wp_mail (managed by client's SMTP plugin)
    // -------------------------------------------------------
    public function send_verification_token() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $email     = !empty($_POST['email']) ? sanitize_email($_POST['email'])      : '';
        $telephone = !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

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

        if (empty($visiteur->email)) {
            wp_send_json_error(array('message' => 'Aucune adresse email associée à ce compte. Contactez-nous directement pour modifier vos informations.'));
            wp_die();
        }

        $token      = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + 15 * 60);

        $wpdb->update(
            $table_name,
            array('verification_token' => $token_hash, 'token_expires_at' => $expires_at),
            array('id' => $visiteur->id)
        );

        $to      = $visiteur->email;
        $subject = 'Votre code de vérification';
        $message = "Bonjour " . $visiteur->prenom . ",\n\n"
                 . "Votre code de vérification pour modifier votre fiche est :\n\n"
                 . "    " . $token . "\n\n"
                 . "Ce code est valable 15 minutes.\n\n"
                 . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\n"
                 . "Cordialement,\nL'équipe";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $sent    = wp_mail($to, $subject, $message, $headers);

        if (!$sent) {
            wp_send_json_error(array('message' => "Impossible d'envoyer l'email de vérification. Veuillez contacter l'administrateur."));
            wp_die();
        }

        wp_send_json_success(array(
            'message'      => 'Code envoyé.',
            'masked_email' => $this->mask_email($visiteur->email),
            'visitor_id'   => $visiteur->id,
        ));
        wp_die();
    }

    // -------------------------------------------------------
    //  Token: verification
    // -------------------------------------------------------
    public function verify_token() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $visitor_id = intval($_POST['visitor_id'] ?? 0);
        $token      = sanitize_text_field(trim($_POST['token'] ?? ''));

        if (!$visitor_id || empty($token)) {
            wp_send_json_error(array('message' => 'Données manquantes.'));
            wp_die();
        }

        $visiteur = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $visitor_id));

        if (!$visiteur) {
            wp_send_json_error(array('message' => 'Visiteur introuvable.'));
            wp_die();
        }

        if (empty($visiteur->token_expires_at) || strtotime($visiteur->token_expires_at) < time()) {
            wp_send_json_error(array('message' => 'Le code a expiré. Veuillez en demander un nouveau.'));
            wp_die();
        }

        $token_hash = hash('sha256', $token);
        if (!hash_equals($visiteur->verification_token ?? '', $token_hash)) {
            wp_send_json_error(array('message' => 'Code incorrect. Vérifiez votre email et réessayez.'));
            wp_die();
        }

        $wpdb->update(
            $table_name,
            array('verification_token' => null, 'token_expires_at' => null),
            array('id' => $visitor_id)
        );

        wp_send_json_success(array(
            'id'                   => $visiteur->id,
            'civilite'             => $visiteur->civilite,
            'nom'                  => $visiteur->nom,
            'prenom'               => $visiteur->prenom,
            'postal_code'          => $visiteur->postal_code,
            'city'                 => $visiteur->city,
            'nom_objet'            => $visiteur->nom_objet,
            'marque'               => $visiteur->marque,
            'age_objet'            => $visiteur->age_objet,
            'poids_objet'          => $visiteur->poids_objet,
            'description_probleme' => $visiteur->description_probleme,
        ));
        wp_die();
    }

    // -------------------------------------------------------
    //  Utility: email masking
    // -------------------------------------------------------
    private function mask_email($email) {
        $parts        = explode('@', $email);
        $local        = $parts[0];
        $domain       = $parts[1] ?? '';
        $masked_local = substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 1));
        return $masked_local . '@' . $domain;
    }

    // -------------------------------------------------------
    //  find_visitor (legacy — not used in token flow)
    // -------------------------------------------------------
    public function find_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $email     = !empty($_POST['email']) ? sanitize_email($_POST['email'])      : '';
        $telephone = !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

        if (empty($email) && empty($telephone)) {
            wp_send_json_error(array('message' => 'Veuillez fournir un email ou un numéro de téléphone.'));
            wp_die();
        }

        $visiteur = null;
        if (!empty($email)) {
            $visiteur = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE email = %s ORDER BY date_creation DESC LIMIT 1", $email
            ));
        }
        if (!$visiteur && !empty($telephone)) {
            $visiteur = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE numero_telephone = %s ORDER BY date_creation DESC LIMIT 1", $telephone
            ));
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
            'postal_code'          => $visiteur->postal_code,
            'city'                 => $visiteur->city,
            'nom_objet'            => $visiteur->nom_objet,
            'marque'               => $visiteur->marque,
            'age_objet'            => $visiteur->age_objet,
            'poids_objet'          => $visiteur->poids_objet,
            'description_probleme' => $visiteur->description_probleme,
        ));
        wp_die();
    }

    // -------------------------------------------------------
    //  save_visitor
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
                wp_send_json_error(array('field' => 'email', 'message' => 'Cette adresse email est déjà utilisée. Utilisez "Modifier mes informations" si vous souhaitez mettre à jour votre fiche.'));
                wp_die();
            }
        }

        $data = array(
            'civilite'             => sanitize_text_field($_POST['civilite']),
            'nom'                  => sanitize_text_field(strtoupper($_POST['nom'])),
            'prenom'               => sanitize_text_field($_POST['prenom']),
            'email'                => $email,
            'numero_telephone'     => $telephone,
            'postal_code'          => sanitize_text_field($_POST['postal_code']),
            'city'                 => !empty($_POST['city'])        ? sanitize_text_field($_POST['city'])        : 'Non renseignée',
            'est_electrique'       => sanitize_text_field($_POST['est_electrique']),
            'nom_objet'            => !empty($_POST['nom_objet'])   ? sanitize_text_field($_POST['nom_objet'])   : '',
            'marque'               => !empty($_POST['marque'])      ? sanitize_text_field($_POST['marque'])      : '',
            'age_objet'            => !empty($_POST['age_objet'])   ? sanitize_text_field($_POST['age_objet'])   : '',
            'poids_objet'          => !empty($_POST['poids_objet']) ? sanitize_text_field($_POST['poids_objet']) : '',
            'description_probleme' => sanitize_textarea_field($_POST['description_probleme']),
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_send_json_error(array('message' => "Erreur lors de l'enregistrement en base."));
            wp_die();
        }

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

        $response_visitor = wp_remote_post('http://172.16.0.1:8000/api/v1/visitors', array(
            'method'  => 'POST', 'timeout' => 15, 'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body'    => json_encode($data_backend),
        ));

        if (is_wp_error($response_visitor)) {
            wp_send_json_error(array('message' => 'Erreur réseau visiteur.')); wp_die();
        }

        $status_visitor = wp_remote_retrieve_response_code($response_visitor);
        $body_visitor   = wp_remote_retrieve_body($response_visitor);

        if ($status_visitor < 200 || $status_visitor >= 300) {
            wp_send_json_error(array('message' => "Backend visiteur refusé (code $status_visitor).", 'details' => json_decode($body_visitor)));
            wp_die();
        }

        $body_visitor_decoded = json_decode($body_visitor, true);
        $backend_visitor_id   = $body_visitor_decoded['id'] ?? null;

        if (!$backend_visitor_id) {
            wp_send_json_error(array('message' => 'ID visiteur backend introuvable.')); wp_die();
        }

        $data_backend_item = array(
            'weight'      => !empty($_POST['poids_objet']) ? sanitize_text_field($_POST['poids_objet']) : '',
            'age'         => !empty($_POST['age_objet'])   ? sanitize_text_field($_POST['age_objet'])   : '',
            'name'        => !empty($_POST['nom_objet'])   ? sanitize_text_field($_POST['nom_objet'])   : '',
            'is_electric' => sanitize_text_field($_POST['est_electrique']) === 'oui',
            'brand'       => !empty($_POST['marque'])      ? sanitize_text_field($_POST['marque'])      : '',
        );

        $response_item = wp_remote_post('http://172.16.0.1:8000/api/v1/visitors/' . $backend_visitor_id . '/items', array(
            'method'  => 'POST', 'timeout' => 15, 'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body'    => json_encode($data_backend_item),
        ));

        $status_item = wp_remote_retrieve_response_code($response_item);
        $body_item   = wp_remote_retrieve_body($response_item);

        if (is_wp_error($response_item) || $status_item < 200 || $status_item >= 300) {
            wp_send_json_error(array('message' => "Backend item refusé (code $status_item).", 'details' => json_decode($body_item)));
            wp_die();
        }

        wp_send_json_success(array('message' => 'Visiteur enregistré avec succès !'));
        wp_die();
    }

    // -------------------------------------------------------
    //  check_email
    // -------------------------------------------------------
    public function check_email() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';
        $email      = !empty($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email)) { wp_send_json_success(array('available' => true)); wp_die(); }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s LIMIT 1", $email));

        if ($existing) {
            wp_send_json_error(array('message' => 'Cette adresse email est déjà utilisée. Utilisez "Modifier mes informations" si vous souhaitez mettre à jour votre fiche.'));
        } else {
            wp_send_json_success(array('available' => true));
        }
        wp_die();
    }

    // -------------------------------------------------------
    //  update_visitor
    // -------------------------------------------------------
    public function update_visitor() {
        check_ajax_referer('visitor_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'visiteurs';

        $id = intval($_POST['id'] ?? 0);
        if (!$id) { wp_send_json_error(array('message' => 'Identifiant invalide.')); wp_die(); }

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $id));
        if (!$exists) { wp_send_json_error(array('message' => 'Visiteur introuvable.')); wp_die(); }

        $data = array(
            'civilite'             => sanitize_text_field($_POST['civilite']),
            'nom'                  => sanitize_text_field(strtoupper($_POST['nom'])),
            'prenom'               => sanitize_text_field($_POST['prenom']),
            'postal_code'          => sanitize_text_field($_POST['postal_code']),
            'city'                 => !empty($_POST['city'])        ? sanitize_text_field($_POST['city'])        : 'Non renseignée',
            'nom_objet'            => !empty($_POST['nom_objet'])   ? sanitize_text_field($_POST['nom_objet'])   : '',
            'marque'               => !empty($_POST['marque'])      ? sanitize_text_field($_POST['marque'])      : '',
            'age_objet'            => !empty($_POST['age_objet'])   ? sanitize_text_field($_POST['age_objet'])   : '',
            'poids_objet'          => !empty($_POST['poids_objet']) ? sanitize_text_field($_POST['poids_objet']) : '',
            'description_probleme' => sanitize_textarea_field($_POST['description_probleme']),
        );

        $result = $wpdb->update($table_name, $data, array('id' => $id));

        if ($result === false) {
            wp_send_json_error(array('message' => "Erreur lors de la mise à jour en base."));
            wp_die();
        }

        $data_backend = array(
            'title'        => sanitize_text_field($_POST['civilite']),
            'name'         => sanitize_text_field(strtoupper($_POST['nom'])),
            'surname'      => sanitize_text_field($_POST['prenom']),
            'city'         => !empty($_POST['city']) ? sanitize_text_field($_POST['city']) : 'Non renseignée',
            'source'       => 'wordpress_form',
            'zip_code'     => sanitize_text_field($_POST['postal_code']),
            'notification' => false,
        );

        $response_visitor = wp_remote_post('http://172.16.0.1:8000/api/v1/visitors/' . $id, array(
            'method'  => 'PATCH', 'timeout' => 15, 'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body'    => json_encode($data_backend),
        ));

        if (is_wp_error($response_visitor)) {
            wp_send_json_error(array('message' => 'Erreur réseau visiteur.')); wp_die();
        }

        $status_visitor = wp_remote_retrieve_response_code($response_visitor);
        $body_visitor   = wp_remote_retrieve_body($response_visitor);

        if ($status_visitor < 200 || $status_visitor >= 300) {
            wp_send_json_error(array('message' => "Backend visiteur refusé (code $status_visitor).", 'details' => json_decode($body_visitor)));
            wp_die();
        }

        $data_backend_item = array(
            'weight'      => !empty($_POST['poids_objet'])    ? sanitize_text_field($_POST['poids_objet'])    : '',
            'age'         => !empty($_POST['age_objet'])      ? sanitize_text_field($_POST['age_objet'])      : '',
            'name'        => !empty($_POST['nom_objet'])      ? sanitize_text_field($_POST['nom_objet'])      : '',
            'is_electric' => !empty($_POST['est_electrique']) ? sanitize_text_field($_POST['est_electrique']) : '',
            'brand'       => !empty($_POST['marque'])         ? sanitize_text_field($_POST['marque'])         : '',
        );

        $response_item = wp_remote_post('http://172.16.0.1:8000/api/v1/items/' . $id, array(
            'method'  => 'PATCH', 'timeout' => 15, 'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body'    => json_encode($data_backend_item),
        ));

        $status_item = wp_remote_retrieve_response_code($response_item);
        $body_item   = wp_remote_retrieve_body($response_item);

        if (is_wp_error($response_item) || $status_item < 200 || $status_item >= 300) {
            wp_send_json_error(array('message' => "Backend item refusé (code $status_item).", 'details' => json_decode($body_item)));
            wp_die();
        }

        wp_send_json_success(array('message' => 'Modifications enregistrées avec succès !'));
        wp_die();
    }

    // -------------------------------------------------------
    //  Admin menu
    // -------------------------------------------------------
    public function add_admin_menu() {
        add_menu_page(
            'Visiteurs', 'Visiteurs', 'manage_options',
            'formulaire-visiteurs', array($this, 'admin_page'),
            'dashicons-id', 25
        );
    }

    // -------------------------------------------------------
    //  Admin page: visitor list
    // -------------------------------------------------------
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
                        <th>Email</th><th>Téléphone</th><th>Code postal</th><th>Ville</th>
                        <th>Électrique</th><th>Nom objet</th><th>Marque</th><th>Âge</th><th>Poids</th>
                        <th>Description Problème</th><th>Date</th>
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