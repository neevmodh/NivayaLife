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
import inviteCountdown from './alpine/invite-countdown';
import copyLink from './alpine/copy-link';
import inviteSignupForm from './alpine/invite-signup';
import familyAddPage from './alpine/family-add-page';
import reportUpload from './alpine/report-upload';
import reportProcessing from './alpine/report-processing';
import metricCompare from './alpine/metric-compare';
import shareActions from './alpine/share-actions';
import assistantChat from './alpine/assistant-chat';

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
Alpine.data('inviteCountdown', inviteCountdown);
Alpine.data('copyLink', copyLink);
Alpine.data('inviteSignupForm', inviteSignupForm);
Alpine.data('familyAddPage', familyAddPage);
Alpine.data('reportUpload', reportUpload);
Alpine.data('reportProcessing', reportProcessing);
Alpine.data('metricCompare', metricCompare);
Alpine.data('shareActions', shareActions);
Alpine.data('assistantChat', assistantChat);

Alpine.start();
