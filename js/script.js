/**
 * Affiche un message d'erreur rouge sous un champ donné.
 * @param {string} fieldId   - id du champ HTML
 * @param {string} message   - texte de l'erreur (vide = effacer)
 */
function setFieldError(fieldId, message) {
    var field = document.getElementById(fieldId);
    if (!field) return;

    var errorId  = fieldId + '_error';
    var errorEl  = document.getElementById(errorId);
    if (!errorEl) {
        errorEl    = document.createElement('span');
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

/**
 * Affiche une erreur sous un groupe de radios.
 * @param {string} groupName  - name="" des radios
 * @param {string} message
 */
function setRadioError(groupName, message) {
    var errorId = 'radio_' + groupName + '_error';
    var errorEl = document.getElementById(errorId);
    if (!errorEl) {
        errorEl    = document.createElement('span');
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

/** Regex code postal France métropole + DOM-TOM (97100–97699) */
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


function nextStep(step) {
    var currentStep = step - 1;

    if (currentStep === 3) {
        validateStepAsync(3, function(valid) {
            if (!valid) return;
            goToStep(step);
        });
        return;
    }

    if (!validateStep(currentStep)) return;
    goToStep(step);
}

function goToStep(step) {
    document.querySelectorAll('#mode-inscription .form-card').forEach(function(card) {
        card.classList.remove('active');
    });
    var next = document.querySelector('#mode-inscription .form-card[data-step="' + step + '"]');
    if (next) next.classList.add('active');
}

function prevStep(step) {
    document.querySelectorAll('#mode-inscription .form-card').forEach(function(card) {
        card.classList.remove('active');
    });
    var prev = document.querySelector('#mode-inscription .form-card[data-step="' + step + '"]');
    if (prev) prev.classList.add('active');
}

/**
 * Valide l'étape en cours et affiche les erreurs sous chaque champ.
 * Retourne true si tout est valide, false sinon.
 */
function validateStep(step) {
    var selector = '#mode-inscription .form-card[data-step="' + step + '"]';
    clearStepErrors(selector);
    var valid = true;

    switch (step) {

        case 1: // Civilité
            if (!document.querySelector('input[name="civilite"]:checked')) {
                setRadioError('civilite', 'Veuillez sélectionner une civilité.');
                valid = false;
            }
            break;

        case 2: // Nom / Prénom
            var nom    = document.getElementById('nom').value.trim();
            var prenom = document.getElementById('prenom').value.trim();
            if (!nom) {
                setFieldError('nom', 'Le nom est obligatoire.');
                valid = false;
            } else if (!/^[A-ZÀ-Ÿa-zà-ÿ\s\-']+$/.test(nom)) {
                setFieldError('nom', 'Le nom ne doit contenir que des lettres.');
                valid = false;
            }
            if (!prenom) {
                setFieldError('prenom', 'Le prénom est obligatoire.');
                valid = false;
            } else if (!/^[A-ZÀ-Ÿa-zà-ÿ\s\-']+$/.test(prenom)) {
                setFieldError('prenom', 'Le prénom ne doit contenir que des lettres.');
                valid = false;
            }
            break;

        case 3: // Email / Téléphone
            var email = document.getElementById('email').value.trim();
            var tel   = document.getElementById('numero_telephone').value.trim();

            if (!email && !tel) {
                setFieldError('email', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
                setFieldError('numero_telephone', 'Veuillez fournir au moins un email ou un numéro de téléphone.');
                valid = false;
            } else {
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    setFieldError('email', 'L\'adresse email n\'est pas valide.');
                    valid = false;
                }
                // Accepte les formats : 06 12 34 56 78 / 0612345678 / +33612345678
                if (tel && !/^(\+33|0)[1-9](\d{2}){4}$/.test(tel.replace(/[\s.\-]/g, ''))) {
                    setFieldError('numero_telephone', 'Numéro invalide. Format attendu : 06 12 34 56 78 ou +33612345678.');
                    valid = false;
                }
            }
            break;

        case 4: // Catégorie objet
            if (!document.getElementById('objet').value) {
                setFieldError('objet', 'Veuillez sélectionner une catégorie.');
                valid = false;
            }
            break;

        case 5: // Code postal
            var cp = document.getElementById('postal_code').value.trim();
            if (!cp) {
                setFieldError('postal_code', 'Le code postal est obligatoire.');
                valid = false;
            } else if (!isValidPostalCode(cp)) {
                setFieldError('postal_code', 'Code postal invalide. Format attendu : 5 chiffres (ex: 37000 ou 97100).');
                valid = false;
            }
            break;

        case 6: // Électrique
            if (!document.querySelector('input[name="electrique"]:checked')) {
                setRadioError('electrique', 'Veuillez indiquer si l\'objet est électrique.');
                valid = false;
            }
            break;

        case 7: // Description
            if (!document.getElementById('description_probleme').value.trim()) {
                setFieldError('description_probleme', 'Veuillez décrire le problème.');
                valid = false;
            }
            break;
    }

    return valid;
}

/**
 * Version asynchrone de validateStep pour les étapes nécessitant un appel AJAX.
 * @param {number}   step
 * @param {function} callback(isValid)
 */
function validateStepAsync(step, callback) {
    var selector = '#mode-inscription .form-card[data-step="' + step + '"]';
    clearStepErrors(selector);

    if (step === 3) {
        var email = document.getElementById('email').value.trim();
        var tel   = document.getElementById('numero_telephone').value.trim();

        // Validation format d'abord (synchrone)
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

        // Vérification doublon email côté serveur
        if (!email) return callback(true); // Pas d'email = pas de doublon à vérifier

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

function submitForm() {
    if (!validateStep(7)) return;

    var data = {
        action:               'save_visitor',
        nonce:                formData.nonce,
        civilite:             document.querySelector('input[name="civilite"]:checked').value,
        nom:                  document.getElementById('nom').value.trim(),
        prenom:               document.getElementById('prenom').value.trim(),
        email:                document.getElementById('email').value.trim(),
        numero_telephone:     document.getElementById('numero_telephone').value.trim(),
        objet:                document.getElementById('objet').value,
        postal_code:          document.getElementById('postal_code').value.trim(),
        est_electrique:       document.querySelector('input[name="electrique"]:checked').value,
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



document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('switchToEdit').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('mode-inscription').style.display  = 'none';
        document.getElementById('mode-modification').style.display = 'block';
        showEditStep(1);
        hideMessage('editSearchMessage');
        hideMessage('editUpdateMessage');
    });

    document.getElementById('switchToRegister').addEventListener('click', function() {
        document.getElementById('mode-modification').style.display = 'none';
        document.getElementById('mode-inscription').style.display  = 'block';
    });
});

function showEditStep(step) {
    document.querySelectorAll('#mode-modification .form-card').forEach(function(card) {
        card.classList.remove('active');
    });
    var target = document.querySelector('#mode-modification .form-card[data-edit-step="' + step + '"]');
    if (target) target.classList.add('active');
}

function backToEditSearch() {
    showEditStep(1);
    hideMessage('editUpdateMessage');
}



function findVisitor() {
    hideMessage('editSearchMessage');

    var email = document.getElementById('edit_email_search').value.trim();
    var phone = document.getElementById('edit_phone_search').value.trim();

    if (!email && !phone) {
        showMessage('editSearchMessage', 'Veuillez saisir un email ou un numéro de téléphone.', false);
        return;
    }

    jQuery.post(formData.ajax_url, {
        action: 'find_visitor',
        nonce:  formData.nonce,
        email:  email,
        phone:  phone
    }, function(response) {
        if (response.success) {
            var v = response.data;
            document.getElementById('edit_visitor_id').value           = v.id;
            document.getElementById('edit_nom').value                  = v.nom;
            document.getElementById('edit_prenom').value               = v.prenom;
            document.getElementById('edit_objet').value                = v.objet;
            document.getElementById('edit_postal_code').value          = v.postal_code;
            document.getElementById('edit_description_probleme').value = v.description_probleme;

            var civiliteInput = document.querySelector('input[name="edit_civilite"][value="' + v.civilite + '"]');
            if (civiliteInput) civiliteInput.checked = true;

            showEditStep(2);
        } else {
            showMessage('editSearchMessage', response.data.message, false);
        }
    });
}

function updateVisitor() {
    hideMessage('editUpdateMessage');
    clearStepErrors('#mode-modification .form-card[data-edit-step="2"]');
    var valid = true;
    var scope = '#mode-modification';

    // Civilité
    if (!document.querySelector('input[name="edit_civilite"]:checked')) {
        setRadioError('edit_civilite', 'Veuillez sélectionner une civilité.', scope);
        valid = false;
    }

    // Nom
    var nom = document.getElementById('edit_nom').value.trim();
    if (!nom) {
        setFieldError('edit_nom', 'Le nom est obligatoire.');
        valid = false;
    } else if (!/^[A-ZÀ-Ÿa-zà-ÿ\s\-']+$/.test(nom)) {
        setFieldError('edit_nom', 'Le nom ne doit contenir que des lettres.');
        valid = false;
    }

    // Prénom
    var prenom = document.getElementById('edit_prenom').value.trim();
    if (!prenom) {
        setFieldError('edit_prenom', 'Le prénom est obligatoire.');
        valid = false;
    } else if (!/^[A-ZÀ-Ÿa-zà-ÿ\s\-']+$/.test(prenom)) {
        setFieldError('edit_prenom', 'Le prénom ne doit contenir que des lettres.');
        valid = false;
    }

    // Catégorie
    if (!document.getElementById('edit_objet').value) {
        setFieldError('edit_objet', 'Veuillez sélectionner une catégorie.');
        valid = false;
    }

    // Code postal — obligatoire et format validé
    var cp = document.getElementById('edit_postal_code').value.trim();
    if (!cp) {
        setFieldError('edit_postal_code', 'Le code postal est obligatoire.');
        valid = false;
    } else if (!isValidPostalCode(cp)) {
        setFieldError('edit_postal_code', 'Code postal invalide. Format attendu : 5 chiffres (ex: 37000 ou 97100).');
        valid = false;
    }

    // Description
    if (!document.getElementById('edit_description_probleme').value.trim()) {
        setFieldError('edit_description_probleme', 'Veuillez décrire le problème.');
        valid = false;
    }

    if (!valid) return;

    jQuery.post(formData.ajax_url, {
        action:               'update_visitor',
        nonce:                formData.nonce,
        id:                   document.getElementById('edit_visitor_id').value,
        civilite:             document.querySelector('input[name="edit_civilite"]:checked').value,
        nom:                  document.getElementById('edit_nom').value.trim(),
        prenom:               document.getElementById('edit_prenom').value.trim(),
        objet:                document.getElementById('edit_objet').value,
        postal_code:          document.getElementById('edit_postal_code').value.trim(),
        description_probleme: document.getElementById('edit_description_probleme').value.trim()
    }, function(response) {
        if (response.success) {
            showMessage('editUpdateMessage', response.data.message, true);
        } else {
            showMessage('editUpdateMessage', response.data.message, false);
        }
    });
}