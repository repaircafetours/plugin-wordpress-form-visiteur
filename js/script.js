// -------------------------------------------------------
//  Utilities: field messages and errors
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
        el.textContent   = '';
        el.style.display = 'none';
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
//  Registration navigation (multi-steps)
// -------------------------------------------------------

function nextStep(step) {
    var currentStep = step - 1;
    if (currentStep === 3) {
        validateStepAsync(3, function(valid) {
            if (valid) goToStep(step);
        });
        return;
    }
    if (!validateStep(currentStep)) return;
    goToStep(step);
}

function goToStep(step) {
    document.querySelectorAll('#mode-inscription .form-card').forEach(function(c) {
        c.classList.remove('active');
    });
    var next = document.querySelector('#mode-inscription .form-card[data-step="' + step + '"]');
    if (next) next.classList.add('active');
}

function prevStep(step) {
    document.querySelectorAll('#mode-inscription .form-card').forEach(function(c) {
        c.classList.remove('active');
    });
    var prev = document.querySelector('#mode-inscription .form-card[data-step="' + step + '"]');
    if (prev) prev.classList.add('active');
}

// -------------------------------------------------------
//  Registration step validation
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
            if (!nom || !prenom) return false;
            if (!nom.value.trim()) {
                setFieldError('nom', 'Le nom est obligatoire.');
                valid = false;
            } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(nom.value.trim())) {
                setFieldError('nom', 'Le nom ne doit contenir que des lettres.');
                valid = false;
            }
            if (!prenom.value.trim()) {
                setFieldError('prenom', 'Le prénom est obligatoire.');
                valid = false;
            } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(prenom.value.trim())) {
                setFieldError('prenom', 'Le prénom ne doit contenir que des lettres.');
                valid = false;
            }
            break;

        case 3:
            var email = document.getElementById('email');
            var tel   = document.getElementById('numero_telephone');
            if (!email || !tel) return false;
            var emailVal = email.value.trim();
            var telVal   = tel.value.trim();
            if (!emailVal && !telVal) {
                setFieldError('email', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
                setFieldError('numero_telephone', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
                valid = false;
            } else {
                if (emailVal && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
                    setFieldError('email', "L'adresse email n'est pas valide.");
                    valid = false;
                }
                if (telVal && !/^(\+33|0)[1-9](\d{2}){4}$/.test(telVal.replace(/[\s.\-]/g, ''))) {
                    setFieldError('numero_telephone', 'Numéro invalide. Format attendu : 06 12 34 56 78 ou +33612345678.');
                    valid = false;
                }
            }
            break;

        case 4:
            var cpEl   = document.getElementById('postal_code');
            var cityEl = document.getElementById('city');
            if (!cpEl || !cityEl) return false;
            var cp   = cpEl.value.trim();
            var city = cityEl.value.trim();
            if (!cp) {
                setFieldError('postal_code', 'Le code postal est obligatoire.');
                valid = false;
            } else if (!isValidPostalCode(cp)) {
                setFieldError('postal_code', 'Code postal invalide.');
                valid = false;
            }
            if (!city) {
                setFieldError('city', 'La ville est obligatoire.');
                valid = false;
            } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(city)) {
                setFieldError('city', 'La ville ne doit contenir que des lettres.');
                valid = false;
            }
            break;

        case 5:
            if (!document.querySelector('input[name="electrique"]:checked')) {
                setRadioError('electrique', "Veuillez indiquer si l'objet est électrique.");
                valid = false;
            }
            var nomObjet = document.getElementById('nom_objet');
            if (!nomObjet || !nomObjet.value.trim()) {
                setFieldError('nom_objet', "Le nom de l'objet est obligatoire.");
                valid = false;
            }
            break;

        case 6:
            var descEl = document.getElementById('description_probleme');
            if (!descEl || !descEl.value.trim()) {
                setFieldError('description_probleme', 'Veuillez décrire le problème.');
                valid = false;
            }
            break;
    }

    return valid;
}

function validateStepAsync(step, callback) {
    var selector = '#mode-inscription .form-card[data-step="' + step + '"]';
    clearStepErrors(selector);

    if (step === 3) {
        var email = document.getElementById('email').value.trim();
        var tel   = document.getElementById('numero_telephone').value.trim();

        if (!email && !tel) {
            setFieldError('email', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
            setFieldError('numero_telephone', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
            return callback(false);
        }
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setFieldError('email', "L'adresse email n'est pas valide.");
            return callback(false);
        }
        if (tel && !/^(\+33|0)[1-9](\d{2}){4}$/.test(tel.replace(/[\s.\-]/g, ''))) {
            setFieldError('numero_telephone', 'Numéro invalide. Format attendu : 06 12 34 56 78 ou +33612345678.');
            return callback(false);
        }
        if (!email) return callback(true);

        jQuery.post(formData.ajax_url, {
            action: 'check_email',
            nonce:  formData.nonce,
            email:  email
        }, function(response) {
            if (response.success) {
                callback(true);
            } else {
                setFieldError('email', response.data.message);
                callback(false);
            }
        });
    } else {
        callback(validateStep(step));
    }
}

// -------------------------------------------------------
//  Registration submission
// -------------------------------------------------------

function submitForm() {
    if (!validateStep(6)) return;

    var data = {
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
    };

    jQuery.post(formData.ajax_url, data, function(response) {
        if (response.success) {
            showMessage('formMessage', response.data.message, true);
            document.querySelectorAll('#mode-inscription .form-card').forEach(function(c) {
                c.classList.remove('active');
            });
        } else {
            showMessage('formMessage', response.data.message, false);
        }
    });
}

// -------------------------------------------------------
//  DOMContentLoaded: listeners
// -------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {

    // Switch to edit mode
    document.getElementById('switchToEdit').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('mode-inscription').style.display  = 'none';
        document.getElementById('mode-modification').style.display = 'block';
        showEditStep(1);
        hideMessage('editSearchMessage');
        hideMessage('editTokenMessage');
        hideMessage('editUpdateMessage');
    });

    // Return to registration
    document.getElementById('switchToRegister').addEventListener('click', function() {
        document.getElementById('mode-modification').style.display = 'none';
        document.getElementById('mode-inscription').style.display  = 'block';
    });

    // Resend code
    document.getElementById('resendToken').addEventListener('click', function(e) {
        e.preventDefault();
        hideMessage('editTokenMessage');

        var email = document.getElementById('edit_email_search').value.trim();
        var phone = document.getElementById('edit_phone_search').value.trim();

        jQuery.post(formData.ajax_url, {
            action: 'send_verification_token',
            nonce:  formData.nonce,
            email:  email,
            phone:  phone
        }, function(response) {
            if (response.success) {
                showMessage('editTokenMessage', 'Code renvoyé à ' + response.data.masked_email, true);
            } else {
                showMessage('editTokenMessage', response.data.message, false);
            }
        });
    });
});

// -------------------------------------------------------
//  Modification navigation (multi-steps)
// -------------------------------------------------------

function showEditStep(step) {
    document.querySelectorAll('#mode-modification .form-card').forEach(function(c) {
        c.classList.remove('active');
    });
    var target = document.querySelector('#mode-modification .form-card[data-edit-step="' + step + '"]');
    if (target) target.classList.add('active');
}

function backToEditSearch() {
    showEditStep(1);
    hideMessage('editUpdateMessage');
}

function backToEditSearchFromToken() {
    showEditStep(1);
    hideMessage('editTokenMessage');
}

// -------------------------------------------------------
//  Step 1 modification: search + token sending
// -------------------------------------------------------

function findVisitor() {
    hideMessage('editSearchMessage');

    var email = document.getElementById('edit_email_search').value.trim();
    var phone = document.getElementById('edit_phone_search').value.trim();

    if (!email && !phone) {
        showMessage('editSearchMessage', 'Veuillez saisir un email ou un numéro de téléphone.', false);
        return;
    }

    jQuery.post(formData.ajax_url, {
        action: 'send_verification_token',
        nonce:  formData.nonce,
        email:  email,
        phone:  phone
    }, function(response) {
        if (response.success) {
            document.getElementById('edit_visitor_id').value = response.data.visitor_id;
            document.getElementById('tokenSentToEmail').textContent =
                'Un code de vérification a été envoyé à ' + response.data.masked_email;
            showEditStep(2);
        } else {
            showMessage('editSearchMessage', response.data.message, false);
        }
    });
}

// -------------------------------------------------------
//  Step 2 modification: token verification
// -------------------------------------------------------

function verifyToken() {
    hideMessage('editTokenMessage');

    var visitorId = document.getElementById('edit_visitor_id').value;
    var token     = document.getElementById('edit_token_input').value.trim();

    if (!token) {
        showMessage('editTokenMessage', 'Veuillez saisir le code de vérification.', false);
        return;
    }

    jQuery.post(formData.ajax_url, {
        action:     'verify_token',
        nonce:      formData.nonce,
        visitor_id: visitorId,
        token:      token
    }, function(response) {
        if (response.success) {
            var v = response.data;
            document.getElementById('edit_nom').value                  = v.nom;
            document.getElementById('edit_prenom').value               = v.prenom;
            document.getElementById('edit_postal_code').value          = v.postal_code;
            document.getElementById('edit_city').value                 = v.city;
            document.getElementById('edit_nom_objet').value            = v.nom_objet   || '';
            document.getElementById('edit_marque').value               = v.marque      || '';
            document.getElementById('edit_age_objet').value            = v.age_objet   || '';
            document.getElementById('edit_poids_objet').value          = v.poids_objet || '';
            document.getElementById('edit_description_probleme').value = v.description_probleme || '';

            var civiliteInput = document.querySelector('input[name="edit_civilite"][value="' + v.civilite + '"]');
            if (civiliteInput) civiliteInput.checked = true;

            document.getElementById('edit_token_input').value = '';
            showEditStep(3);
        } else {
            showMessage('editTokenMessage', response.data.message, false);
        }
    });
}

// -------------------------------------------------------
//  Step 3 modification: validation + sending
// -------------------------------------------------------

function updateVisitor() {
    hideMessage('editUpdateMessage');
    clearStepErrors('#mode-modification .form-card[data-edit-step="3"]');
    var valid = true;

    if (!document.querySelector('input[name="edit_civilite"]:checked')) {
        setRadioError('edit_civilite', 'Veuillez sélectionner une civilité.');
        valid = false;
    }

    var nomEl = document.getElementById('edit_nom');
    if (!nomEl) return;
    if (!nomEl.value.trim()) {
        setFieldError('edit_nom', 'Le nom est obligatoire.');
        valid = false;
    } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(nomEl.value.trim())) {
        setFieldError('edit_nom', 'Le nom ne doit contenir que des lettres.');
        valid = false;
    }

    var prenomEl = document.getElementById('edit_prenom');
    if (!prenomEl) return;
    if (!prenomEl.value.trim()) {
        setFieldError('edit_prenom', 'Le prénom est obligatoire.');
        valid = false;
    } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(prenomEl.value.trim())) {
        setFieldError('edit_prenom', 'Le prénom ne doit contenir que des lettres.');
        valid = false;
    }

    var cpEl = document.getElementById('edit_postal_code');
    if (!cpEl) return;
    if (!cpEl.value.trim()) {
        setFieldError('edit_postal_code', 'Le code postal est obligatoire.');
        valid = false;
    } else if (!isValidPostalCode(cpEl.value.trim())) {
        setFieldError('edit_postal_code', 'Code postal invalide. Format attendu : 5 chiffres (ex: 37000 ou 97100).');
        valid = false;
    }

    var cityEl = document.getElementById('edit_city');
    if (!cityEl) return;
    if (!cityEl.value.trim()) {
        setFieldError('edit_city', 'La ville est obligatoire.');
        valid = false;
    } else if (!/^[A-Za-zÀ-ÿ\s\-'.]+$/.test(cityEl.value.trim())) {
        setFieldError('edit_city', 'La ville ne doit contenir que des lettres.');
        valid = false;
    }

    var nomObjetEl = document.getElementById('edit_nom_objet');
    if (!nomObjetEl) return;
    if (!nomObjetEl.value.trim()) {
        setFieldError('edit_nom_objet', "Le nom de l'objet est obligatoire.");
        valid = false;
    }

    var ageObjetEl = document.getElementById('edit_age_objet');
    if (!ageObjetEl) return;
    if (!ageObjetEl.value.trim()) {
        setFieldError('edit_age_objet', "L'âge de l'objet est obligatoire.");
        valid = false;
    }

    var descEl = document.getElementById('edit_description_probleme');
    if (!descEl || !descEl.value.trim()) {
        setFieldError('edit_description_probleme', 'Veuillez décrire le problème.');
        valid = false;
    }

    if (!valid) return;

    jQuery.post(formData.ajax_url, {
        action:               'update_visitor',
        nonce:                formData.nonce,
        id:                   document.getElementById('edit_visitor_id').value,
        civilite:             document.querySelector('input[name="edit_civilite"]:checked').value,
        nom:                  nomEl.value.trim(),
        prenom:               prenomEl.value.trim(),
        postal_code:          cpEl.value.trim(),
        city:                 cityEl.value.trim(),
        nom_objet:            nomObjetEl.value.trim(),
        marque:               document.getElementById('edit_marque') ? document.getElementById('edit_marque').value.trim() : '',
        age_objet:            ageObjetEl.value.trim(),
        poids_objet:          document.getElementById('edit_poids_objet') ? document.getElementById('edit_poids_objet').value.trim() : '',
        description_probleme: descEl.value.trim()
    }, function(response) {
        if (response.success) {
            showMessage('editUpdateMessage', response.data.message, true);
        } else {
            showMessage('editUpdateMessage', response.data.message, false);
        }
    });
}