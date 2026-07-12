import './bootstrap';

import Alpine from 'alpinejs';

import registrationWizard from './alpine/registration-wizard';
import cameraCapture from './alpine/camera-capture';
import locationSelect from './alpine/location-select';
import bmiGauge from './alpine/bmi-gauge';
import tagInput from './alpine/tag-input';
import bloodGroupSelect from './alpine/blood-group-select';
import passwordStrength from './alpine/password-strength';
import darkMode from './alpine/dark-mode';
import ajaxForm from './alpine/ajax-form';

window.Alpine = Alpine;

Alpine.data('registrationWizard', registrationWizard);
Alpine.data('cameraCapture', cameraCapture);
Alpine.data('locationSelect', locationSelect);
Alpine.data('bmiGauge', bmiGauge);
Alpine.data('tagInput', tagInput);
Alpine.data('bloodGroupSelect', bloodGroupSelect);
Alpine.data('passwordStrength', passwordStrength);
Alpine.data('darkMode', darkMode);
Alpine.data('ajaxForm', ajaxForm);

Alpine.start();
