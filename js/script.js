// -------------------------------------------------------
//  Utilitaires
// -------------------------------------------------------

function setFieldError(fieldId, message) {
    var field = document.getElementById(fieldId);
    if (!field) return;
    var errorId = fieldId + '_error';
    var errorEl = document.getElementById(errorId);
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.id = errorId;
        errorEl.className = 'field-error';
        field.parentNode.insertBefore(errorEl, field.nextSibling);
    }
    if (message) {
        errorEl.textContent   = message;
        errorEl.style.display = 'block';
        field.classList.add('input-error');
    } else {
        errorEl.textContent   = '';
        errorEl.style.display = 'none';
        field.classList.remove('input-error');
    }
}

function setRadioError(groupName, message) {
    var errorId = 'radio_' + groupName + '_error';
    var errorEl = document.getElementById(errorId);
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.id = errorId;
        errorEl.className = 'field-error';
        var group = document.querySelector('[name="' + groupName + '"]');
        if (group) {
            var radioGroup = group.closest('.radio-group');
            if (radioGroup) radioGroup.parentNode.insertBefore(errorEl, radioGroup.nextSibling);
        }
    }
    if (message) {
        errorEl.textContent   = message;
        errorEl.style.display = 'block';
    } else {
        errorEl.textContent   = '';
        errorEl.style.display = 'none';
    }
}

function clearStepErrors(stepSelector) {
    document.querySelectorAll(stepSelector + ' .field-error').forEach(function(el) {
        el.textContent = ''; el.style.display = 'none';
    });
    document.querySelectorAll(stepSelector + ' .input-error').forEach(function(el) {
        el.classList.remove('input-error');
    });
}

function isValidPostalCode(value) {
    return /^(0[1-9]|[1-8]\d|9[0-5])\d{3}$/.test(value)
        || /^97[1-6]\d{2}$/.test(value);
}

function showMessage(elementId, message, isSuccess) {
    var el = document.getElementById(elementId);
    if (!el) return;
    el.textContent   = message;
    el.className     = 'form-message ' + (isSuccess ? 'success' : 'error');
    el.style.display = 'block';
}

function hideMessage(elementId) {
    var el = document.getElementById(elementId);
    if (el) { el.style.display = 'none'; el.textContent = ''; }
}

// -------------------------------------------------------
//  Lecture des paramètres dans l'URL
//  Le backend envoie un lien : /formulaire?visitor=5&token=abc123
// -------------------------------------------------------

function getUrlParam(name) {
    var params = new URLSearchParams(window.location.search);
    return params.get(name);
}

// -------------------------------------------------------
//  Initialisation : détecte si on est en mode modification
// -------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {
    var visitorId    = getUrlParam('visitor');
    var visitorToken = getUrlParam('token');

    if (visitorId && visitorToken) {
        // Mode modification : masque l'inscription, affiche la modification
        document.getElementById('mode-inscription').style.display  = 'none';
        document.getElementById('mode-modification').style.display = 'block';
        loadVisitor(visitorId, visitorToken);
    }
    // Sinon : mode inscription par défaut, rien à faire
});

// -------------------------------------------------------
//  Chargement des données visiteur depuis le backend
// -------------------------------------------------------

function showEditStep(step) {
    document.querySelectorAll('#mode-modification .form-card').forEach(function(c) {
        c.classList.remove('active');
    });
    var target = document.querySelector('#mode-modification .form-card[data-edit-step="' + step + '"]');
    if (target) target.classList.add('active');
}

function loadVisitor(visitorId, visitorToken) {
    showEditStep('loading');

    jQuery.post(formData.ajax_url, {
        action:        'get_visitor',
        nonce:         formData.nonce,
        visitor_id:    visitorId,
        visitor_token: visitorToken
    }, function(response) {
        if (!response.success) {
            document.getElementById('editErrorMessage').textContent =
                response.data.message || 'Lien invalide ou expiré.';
            showEditStep('error');
            return;
        }

        var v    = response.data.visitor;
        var item = response.data.item;

        // Stocke l'ID et le token dans les champs cachés
        document.getElementById('edit_visitor_id').value    = v.id;
        document.getElementById('edit_visitor_token').value = visitorToken;
        document.getElementById('edit_item_id').value       = item ? item.id : '';

        // Pré-remplit les champs visiteur
        document.getElementById('edit_nom').value          = v.name    || '';
        document.getElementById('edit_prenom').value       = v.surname || '';
        document.getElementById('edit_postal_code').value  = v.zip_code || '';
        document.getElementById('edit_city').value         = v.city    || '';

        var civiliteInput = document.querySelector('input[name="edit_civilite"][value="' + v.title + '"]');
        if (civiliteInput) civiliteInput.checked = true;

        // Pré-remplit les champs item
        if (item) {
            document.getElementById('edit_nom_objet').value   = item.name   || '';
            document.getElementById('edit_marque').value      = item.brand  || '';
            document.getElementById('edit_age_objet').value   = item.age    || '';
            document.getElementById('edit_poids_objet').value = item.weight || '';

            var electricVal = item.is_electric ? 'oui' : 'non';
            var electricInput = document.querySelector('input[name="edit_electrique"][value="' + electricVal + '"]');
            if (electricInput) electricInput.checked = true;

            document.getElementById('edit_description_probleme').value = item.description || '';
        }

        showEditStep('form');
    }).fail(function() {
        document.getElementById('editErrorMessage').textContent = 'Erreur réseau. Veuillez réessayer.';
        showEditStep('error');
    });
}

// -------------------------------------------------------
//  Soumission du formulaire de modification
// -------------------------------------------------------

function updateVisitor() {
    hideMessage('editUpdateMessage');
    clearStepErrors('#mode-modification .form-card[data-edit-step="form"]');
    var valid = true;

    if (!document.querySelector('input[name="edit_civilite"]:checked')) {
        setRadioError('edit_civilite', 'Veuillez sélectionner une civilité.');
        valid = false;
    }

    var nomEl = document.getElementById('edit_nom');
    if (!nomEl.value.trim()) {
        setFieldError('edit_nom', 'Le nom est obligatoire.');
        valid = false;
    } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(nomEl.value.trim())) {
        setFieldError('edit_nom', 'Le nom ne doit contenir que des lettres.');
        valid = false;
    }

    var prenomEl = document.getElementById('edit_prenom');
    if (!prenomEl.value.trim()) {
        setFieldError('edit_prenom', 'Le prénom est obligatoire.');
        valid = false;
    } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(prenomEl.value.trim())) {
        setFieldError('edit_prenom', 'Le prénom ne doit contenir que des lettres.');
        valid = false;
    }

    var cpEl = document.getElementById('edit_postal_code');
    if (!cpEl.value.trim()) {
        setFieldError('edit_postal_code', 'Le code postal est obligatoire.');
        valid = false;
    } else if (!isValidPostalCode(cpEl.value.trim())) {
        setFieldError('edit_postal_code', 'Code postal invalide.');
        valid = false;
    }

    var cityEl = document.getElementById('edit_city');
    if (!cityEl.value.trim()) {
        setFieldError('edit_city', 'La ville est obligatoire.');
        valid = false;
    } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(cityEl.value.trim())) {
        setFieldError('edit_city', 'La ville ne doit contenir que des lettres.');
        valid = false;
    }

    var nomObjetEl = document.getElementById('edit_nom_objet');
    if (!nomObjetEl.value.trim()) {
        setFieldError('edit_nom_objet', "Le nom de l'objet est obligatoire.");
        valid = false;
    }

    var descEl = document.getElementById('edit_description_probleme');
    if (!descEl.value.trim()) {
        setFieldError('edit_description_probleme', 'Veuillez décrire le problème.');
        valid = false;
    }

    if (!valid) return;

    jQuery.post(formData.ajax_url, {
        action:               'update_visitor',
        nonce:                formData.nonce,
        visitor_id:           document.getElementById('edit_visitor_id').value,
        visitor_token:        document.getElementById('edit_visitor_token').value,
        item_id:              document.getElementById('edit_item_id').value,
        civilite:             document.querySelector('input[name="edit_civilite"]:checked').value,
        nom:                  nomEl.value.trim(),
        prenom:               prenomEl.value.trim(),
        postal_code:          cpEl.value.trim(),
        city:                 cityEl.value.trim(),
        nom_objet:            nomObjetEl.value.trim(),
        marque:               document.getElementById('edit_marque').value.trim(),
        age_objet:            document.getElementById('edit_age_objet').value.trim(),
        poids_objet:          document.getElementById('edit_poids_objet').value.trim(),
        est_electrique:       (document.querySelector('input[name="edit_electrique"]:checked') || {}).value || '',
        description_probleme: descEl.value.trim()
    }, function(response) {
        if (response.success) {
            showEditStep('success');
        } else {
            showMessage('editUpdateMessage', response.data.message, false);
        }
    }).fail(function() {
        showMessage('editUpdateMessage', 'Erreur réseau. Veuillez réessayer.', false);
    });
}

// -------------------------------------------------------
//  Navigation inscription (multi-steps)
// -------------------------------------------------------

function nextStep(step) {
    var currentStep = step - 1;
    if (currentStep === 3) {
        validateStepAsync(3, function(valid) { if (valid) goToStep(step); });
        return;
    }
    if (!validateStep(currentStep)) return;
    goToStep(step);
}

function goToStep(step) {
    document.querySelectorAll('#mode-inscription .form-card').forEach(function(c) { c.classList.remove('active'); });
    var next = document.querySelector('#mode-inscription .form-card[data-step="' + step + '"]');
    if (next) next.classList.add('active');
}

function prevStep(step) {
    document.querySelectorAll('#mode-inscription .form-card').forEach(function(c) { c.classList.remove('active'); });
    var prev = document.querySelector('#mode-inscription .form-card[data-step="' + step + '"]');
    if (prev) prev.classList.add('active');
}

// -------------------------------------------------------
//  Validation des étapes inscription
// -------------------------------------------------------

function validateStep(step) {
    var selector = '#mode-inscription .form-card[data-step="' + step + '"]';
    clearStepErrors(selector);
    var valid = true;

    switch (step) {
        case 1:
            if (!document.querySelector('input[name="civilite"]:checked')) {
                setRadioError('civilite', 'Veuillez sélectionner une civilité.');
                valid = false;
            }
            break;
        case 2:
            var nom    = document.getElementById('nom');
            var prenom = document.getElementById('prenom');
            if (!nom.value.trim()) { setFieldError('nom', 'Le nom est obligatoire.'); valid = false; }
            else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(nom.value.trim())) { setFieldError('nom', 'Le nom ne doit contenir que des lettres.'); valid = false; }
            if (!prenom.value.trim()) { setFieldError('prenom', 'Le prénom est obligatoire.'); valid = false; }
            else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(prenom.value.trim())) { setFieldError('prenom', 'Le prénom ne doit contenir que des lettres.'); valid = false; }
            break;
        case 3:
            var emailVal = document.getElementById('email').value.trim();
            var telVal   = document.getElementById('numero_telephone').value.trim();
            if (!emailVal && !telVal) {
                setFieldError('email', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
                setFieldError('numero_telephone', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
                valid = false;
            } else {
                if (emailVal && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { setFieldError('email', "L'adresse email n'est pas valide."); valid = false; }
                if (telVal && !/^(\+33|0)[1-9](\d{2}){4}$/.test(telVal.replace(/[\s.\-]/g, ''))) { setFieldError('numero_telephone', 'Numéro invalide.'); valid = false; }
            }
            break;
        case 4:
            var cp   = document.getElementById('postal_code').value.trim();
            var city = document.getElementById('city').value.trim();
            if (!cp) { setFieldError('postal_code', 'Le code postal est obligatoire.'); valid = false; }
            else if (!isValidPostalCode(cp)) { setFieldError('postal_code', 'Code postal invalide.'); valid = false; }
            if (!city) { setFieldError('city', 'La ville est obligatoire.'); valid = false; }
            else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(city)) { setFieldError('city', 'La ville ne doit contenir que des lettres.'); valid = false; }
            break;
        case 5:
            if (!document.querySelector('input[name="electrique"]:checked')) { setRadioError('electrique', "Veuillez indiquer si l'objet est électrique."); valid = false; }
            if (!document.getElementById('nom_objet').value.trim()) { setFieldError('nom_objet', "Le nom de l'objet est obligatoire."); valid = false; }
            break;
        case 6:
            if (!document.getElementById('description_probleme').value.trim()) { setFieldError('description_probleme', 'Veuillez décrire le problème.'); valid = false; }
            break;
    }
    return valid;
}

function validateStepAsync(step, callback) {
    clearStepErrors('#mode-inscription .form-card[data-step="' + step + '"]');
    if (step !== 3) { callback(validateStep(step)); return; }

    var email = document.getElementById('email').value.trim();
    var tel   = document.getElementById('numero_telephone').value.trim();

    if (!email && !tel) {
        setFieldError('email', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
        setFieldError('numero_telephone', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
        return callback(false);
    }
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setFieldError('email', "L'adresse email n'est pas valide."); return callback(false); }
    if (tel && !/^(\+33|0)[1-9](\d{2}){4}$/.test(tel.replace(/[\s.\-]/g, ''))) { setFieldError('numero_telephone', 'Numéro invalide.'); return callback(false); }
    if (!email) return callback(true);

    jQuery.post(formData.ajax_url, { action: 'check_email', nonce: formData.nonce, email: email }, function(response) {
        if (response.success) { callback(true); }
        else { setFieldError('email', response.data.message); callback(false); }
    });
}

// -------------------------------------------------------
//  Demande de lien de modification
// -------------------------------------------------------

function requestEditLink() {
    hideMessage('editRequestMessage');

    var email = document.getElementById('edit_request_email').value.trim();

    if (!email) {
        showMessage('editRequestMessage', 'Veuillez saisir votre adresse email.', false);
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMessage('editRequestMessage', "L'adresse email n'est pas valide.", false);
        return;
    }

    // Désactive le bouton pendant la requête
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Envoi en cours...';

    jQuery.post(formData.ajax_url, {
        action: 'request_edit_link',
        nonce:  formData.nonce,
        email:  email
    }, function(response) {
        btn.disabled = false;
        btn.textContent = 'Recevoir mon lien de modification';

        if (response.success) {
            showMessage('editRequestMessage', response.data.message, true);
            document.getElementById('edit_request_email').value = '';
        } else {
            showMessage('editRequestMessage', response.data.message, false);
        }
    }).fail(function() {
        btn.disabled = false;
        btn.textContent = 'Recevoir mon lien de modification';
        showMessage('editRequestMessage', 'Erreur réseau. Veuillez réessayer.', false);
    });
}


// -------------------------------------------------------
//  Soumission inscription
// -------------------------------------------------------
function submitForm() {
    if (!validateStep(6)) return;

    jQuery.post(formData.ajax_url, {
        action:               'save_visitor',
        nonce:                formData.nonce,
        civilite:             document.querySelector('input[name="civilite"]:checked').value,
        nom:                  document.getElementById('nom').value.trim(),
        prenom:               document.getElementById('prenom').value.trim(),
        email:                document.getElementById('email').value.trim(),
        numero_telephone:     document.getElementById('numero_telephone').value.trim(),
        postal_code:          document.getElementById('postal_code').value.trim(),
        city:                 document.getElementById('city').value.trim(),
        est_electrique:       document.querySelector('input[name="electrique"]:checked').value,
        nom_objet:            document.getElementById('nom_objet').value.trim(),
        marque:               document.getElementById('marque').value.trim(),
        age_objet:            document.getElementById('age_objet').value.trim(),
        poids_objet:          document.getElementById('poids_objet').value.trim(),
        description_probleme: document.getElementById('description_probleme').value.trim()
    }, function(response) {
        if (response.success) {
            showMessage('formMessage', response.data.message, true);
            document.querySelectorAll('#mode-inscription .form-card').forEach(function(c) { c.classList.remove('active'); });
        } else {
            showMessage('formMessage', response.data.message, false);
        }
    });
}