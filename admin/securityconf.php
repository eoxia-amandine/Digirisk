<?php
/* Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/admin/accountant.php
 *	\ingroup    accountant
 *	\brief      Setup page to configure accountant / auditor
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/digiriskresources.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminsecurity'; // To manage different context of search

$id = GETPOST('id', 'int');

$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');
$confirm = GETPOST('confirm', 'alpha');

$fulldayevent = GETPOST('fullday');

$aphour = GETPOST('aphour');
$apmin = GETPOST('apmin');
$p2hour = GETPOST('p2hour');
$p2min = GETPOST('p2min');
// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

$contact = new Contact($db);


if (!$user->admin) accessforbidden();

$error = 0;
$hookmanager->initHooks(array('admincompany', 'globaladmin'));


/*
 * Actions
 */
global $conf;
$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{

	//@todo pertinence getpos ou all_links
	$resources = new DigiriskResources($db);
	$links = $resources->fetchAll();
	foreach ($links as $link) {
		$allLinks[$link->ref]->id = $link->element_id;
		$allLinks[$link->ref]->type = $link->element_type;
	}

	// a priori vu le fonctionnement ce serait pas mal de conserver le fonctionnement avec des fonctions d'insert (semblable à la fonction des const)
	// pour digidoli set resources : update statuts précécents à 0
	// pour digidoli set const : même fonctionnement que dolibarr set const
	// pour fetch : fetch all + fetch spécifique

	$labourdoctor_id 					= GETPOST('labourdoctor_socid', 'int') > 0 ? GETPOST('labourdoctor_socid', 'int') : $allLinks['LabourDoctorSociete']->id ;
	$labourdoctor_socpeopleassigned 	= !empty(GETPOST('labourdoctor_contactid', 'array')) ? GETPOST('labourdoctor_contactid', 'array') : (GETPOST('labourdoctor_contactid', 'int') > 0 ? GETPOST('labourdoctor_contactid', 'int') : $allLinks['LabourDoctorContact']->id);

	$labourinspector_id					= GETPOST('labourinspector_socid', 'int') > 0 ? GETPOST('labourinspector_socid','int') : $allLinks['LabourInspectorSociete']->id;
	$labourinspector_socpeopleassigned 	= !empty(GETPOST('labourinspector_contactid', 'array')) ? GETPOST('labourinspector_contactid','array') : (GETPOST('labourinspector_contactid', 'int') > 0 ? GETPOST('labourinspector_contactid', 'int') : $allLinks['LabourInspectorContact']->id);

	$resources = new DigiriskResources($db);
	$resources->digirisk_dolibarr_set_resources($db,1,  'LabourDoctorSociete',  'societe', $labourdoctor_id, 1);
	$resources->digirisk_dolibarr_set_resources($db,1,  'LabourDoctorContact',  'socpeople', $labourdoctor_socpeopleassigned, 1);
	$resources->digirisk_dolibarr_set_resources($db,1,  'LabourInspectorSociete',  'societe', $labourinspector_id, 1);
	$resources->digirisk_dolibarr_set_resources($db,1,  'LabourInspectorContact',  'socpeople', $labourinspector_socpeopleassigned, 1);

//	$resources->digirisk_dolibarr_set_resources($db, 'LabourInspectorSociete',  1, 'societe', $labourinspector_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'LabourInspectorContact',  1, 'socpeople', $labourinspector_socpeopleassigned);

	$samu_id		 	= GETPOST('samu_socid', 'int') ? GETPOST('samu_socid', 'int') : $allLinks['SAMU']->element ;
	$pompiers_id 		= GETPOST('pompiers_socid', 'int') ? GETPOST('pompiers_socid','int') : $allLinks['Pompiers']->element;
	$police_id 			= GETPOST('police_socid', 'int') ? GETPOST('police_socid', 'int') : $allLinks['Police']->element ;
	$touteurgence_id 	= GETPOST('touteurgence_socid', 'int') ? GETPOST('touteurgence_socid','int') : $allLinks['AllEmergencies']->element;
	$defenseur_id 		= GETPOST('defenseur_socid', 'int') ? GETPOST('defenseur_socid', 'int') : $allLinks['RightsDefender']->element ;
	$antipoison_id 		= GETPOST('antipoison_socid', 'int') ? GETPOST('antipoison_socid','int') : $allLinks['Antipoison']->element;
	$responsible_id 	= GETPOST('responsible_socid', 'int') ? GETPOST('responsible_socid','int') : $allLinks['Responsible']->element;

//@todo fk_user creat
//	$resources->digirisk_dolibarr_set_resources($db, 'SAMU',  1, 'societe', $samu_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'Pompiers',  1, 'societe', $pompiers_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'Police',  1, 'societe', $police_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'AllEmergencies',  1, 'societe',  $touteurgence_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'RightsDefender',  1, 'societe',  $defenseur_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'Antipoison',  1, 'societe', $antipoison_id);
//	$resources->digirisk_dolibarr_set_resources($db, 'Responsible',  1, 'user', $responsible_id);

	dolibarr_set_const($db, "DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION", GETPOST("emplacementCD", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_SOCIETY_DESCRIPTION", GETPOST("description", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_GENERAL_MEANS", GETPOST("moyensgeneraux", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_GENERAL_RULES", GETPOST("consignesgenerales", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_RULES_LOCATION", GETPOST("emplacementRI", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISK_DUER_LOCATION", GETPOST("emplacementDU", 'none'), 'chaine', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}
/*
 * View
 */

$help_url = '';
llxHeader('', $langs->trans("CompanyFoundation"), $help_url);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

dol_fiche_head($head, 'security', $langs->trans("Company"), -1, 'company');
$testo = new DigiriskResources($db);


$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

$countrynotdefined = '<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';
print '<span class="opacitymedium">'.$langs->trans("AccountantDesc")."</span><br>\n";
print "<br>\n";
/**
 * Edit parameters
 */
print "\n".'<script type="text/javascript" language="javascript">';
print '$(document).ready(function () {
		  $("#selectcountry_id").change(function() {
			document.form_index.action.value="updateedit";
			document.form_index.submit();
		  });
	  });';
print '</script>'."\n";

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent editmode">';

if ($conf->societe->enabled)
{
	// MEDECIN DU TRAVAIL
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("LabourDoctor").'</th><th>.<i class="fas fa-briefcase-medical"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$labour_doctor_societe = digirisk_dolibarr_fetch_resources($db, 'LabourDoctorSociete', 'societe');

	// Tiers
	if ($labour_doctor_societe->ref == 'LabourDoctorSociete')
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));

		$societe = new Societe($db);
		$societe->fetch($labour_doctor_societe->element);
		print $form->select_company($labour_doctor_societe->element, 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');

	}
	else
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourdoctor_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'labourdoctor_socid', '', 1, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourdoctor_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Related contact
	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
	//$labour_doctor_contact = digirisk_dolibarr_fetch_resources($db, 'LabourDoctorContact', 'socpeople');
	$labour_doctorpreselectedids = $labour_doctor_contact->element;

	if ($labour_doctor_contact->element) {
		print $form->selectcontacts($labour_doctor_societe->element, $labour_doctor_contact->element, 'labourdoctor_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	else
	{
		$labourdoctorpreselectedids = GETPOST('labourdoctor_contactid', 'int');
		if (GETPOST('labourdoctor_contactid', 'int')) $labourdoctorpreselectedids[GETPOST('labourdoctor_contactid', 'int')] = GETPOST('labourdoctor_contactid', 'int');
		print $form->selectcontacts(GETPOST('labourdoctor_socid', 'int'), $labourdoctorpreselectedids, 'labourdoctor_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourdoctor_contactid');
	}
	print '</td></tr>';

	// INSPECTEUR DU TRAVAIL
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("LabourInspector").'</th><th>.<i class="fas fa-search"></i></th></tr>'."\n";
	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$labour_inspector_societe = digirisk_dolibarr_fetch_resources($db, 'LabourInspectorSociete', 'societe');

	// Tiers
	if ($labour_inspector_societe->ref == 'LabourInspectorSociete')
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));

		$societe = new Societe($db);
		$societe->fetch($labour_inspector_societe->element);
		print $form->select_company($labour_inspector_societe->element, 'labourinspector_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');

	}
	else
	{
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'labourinspector_contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'labourinspector_socid', '', 1, 1, 0, $events, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'labourinspector_socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Related contacts
	print '<tr class="oddeven"><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
	//$labour_inspector_contact = digirisk_dolibarr_fetch_resources($db, 'LabourInspectorContact', 'socpeople');
	$preselectedids = $labour_inspector_contact->element;
	if ($labour_inspector_contact->element) {
		print $form->selectcontacts($labour_inspector_societe->element, $labour_inspector_contact->element , 'labourinspector_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	else
	{
		$preselectedids = GETPOST('labourinspector_contactid', 'int');
		if (GETPOST('labourinspector_contactid', 'int')) $preselectedids[GETPOST('labourinspector_contactid', 'int')] = GETPOST('labourinspector_contactid', 'int');
		print $form->selectcontacts(GETPOST('labourinspector_socid', 'int'), $preselectedids, 'labourinspector_contactid', 1, '', '', 0, 'quatrevingtpercent', false, 0, array(), false, 'multiple', 'labourinspector_contactid');
	}
	print '</td></tr>';

	// SAMU
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("SAMU").'</th><th>.<i class="fas fa-hospital-alt"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$samu_resources = digirisk_dolibarr_fetch_resources($db, 'SAMU', 'societe');

	// Tiers
	if ($samu_resources->ref == 'SAMU')
	{
		$societe = new Societe($db);
		$societe->fetch($samu_resources->element);
		print $form->select_company($samu_resources->element, 'samu_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'samu_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'samu_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Pompiers
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Pompiers").'</th><th>.<i class="fas fa-ambulance"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$pompiers_resources = digirisk_dolibarr_fetch_resources($db, 'Pompiers', 'societe');

	// Tiers
	if ($pompiers_resources->ref == 'Pompiers')
	{
		$societe = new Societe($db);
		$societe->fetch($pompiers_resources->element);
		print $form->select_company($pompiers_resources->element, 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'pompiers_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'pompiers_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Police
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Police").'</th><th>.<i class="fas fa-car"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$police_resources = digirisk_dolibarr_fetch_resources($db, 'Police', 'societe');

	// Tiers
	if ($police_resources->ref == 'Police')
	{
		$societe = new Societe($db);
		$societe->fetch($police_resources->element);
		print $form->select_company($police_resources->element, 'police_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'police_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'police_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Toute Urgence
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("AllEmergencies").'</th><th>.<i class="fas fa-phone"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$touteurgence_resources = digirisk_dolibarr_fetch_resources($db, 'AllEmergencies', 'societe');

	// Tiers
	if ($touteurgence_resources->ref == 'AllEmergencies')
	{
		$societe = new Societe($db);
		$societe->fetch($touteurgence_resources->element);
		print $form->select_company($touteurgence_resources->element, 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'touteurgence_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'touteurgence_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Défenseur des droits
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("RightsDefender").'</th><th>.<i class="fas fa-gavel"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$defenseur_resources = digirisk_dolibarr_fetch_resources($db, 'RightsDefender', 'societe');

	// Tiers
	if ($defenseur_resources->ref == 'RightsDefender')
	{
		$societe = new Societe($db);
		$societe->fetch($defenseur_resources->element);
		print $form->select_company($defenseur_resources->element, 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'defenseur_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'defenseur_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Antipoison
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Antipoison").'</th><th>.<i class="fas fa-skull-crossbones"></i></th></tr>'."\n";

	print '<tr class="oddeven"><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	//$antipoison_resources = digirisk_dolibarr_fetch_resources($db, 'Antipoison', 'societe');

	// Tiers
	if ($antipoison_resources->ref == 'Antipoison')
	{
		$societe = new Societe($db);
		$societe->fetch($antipoison_resources->element);
		print $form->select_company($antipoison_resources->element, 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0, 0, 'minwidth300');

	}
	else
	{
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print $form->select_company($user->socid, 'antipoison_socid', '', 1, 1, 0, 0, 0, 'minwidth300');
		} else {
			print $form->select_company('', 'antipoison_socid', '', 'SelectThirdParty', 1, 0, 0 , 0, 'minwidth300');
		}
	}
	print '</td></tr>';
}

// Consignes de sécurité
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Consignes de sécurité").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Responsable à prévenir

print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("Responsable à prévenir").'</td><td>';
//$responsible_resources = digirisk_dolibarr_fetch_resources($db, 'Responsible', 'user');

// Tiers
if ($responsible_resources->ref == 'Responsible' && $responsible_resources->element > 0)
{
	$user = new User($db);
	$user->fetch($responsible_resources->element);

	print $form->select_dolusers($responsible_resources->element, 'responsible_socid', 0, null, 0, 0, 0, 0, 'minwidth300');
// Téléphone
	print '<tr class="oddeven"><td><label for="name">'.$langs->trans("Téléphone").'</label></td><td>';
	print $user->office_phone;
	print '</td></tr>';

}
else //id = 0
{
	//For external user force the company to user company
	if (!empty($user->socid)) {
		print $form->select_dolusers($user->socid, 'responsible_socid', '', 1, 0, 0, 0, 0, 'minwidth300');
	} else {
		print $form->select_dolusers('', 'responsible_socid', '', '', 0, 0, 0 , 0, 'minwidth300');
	}
}
print '</td></tr>';

// Emplacement de la consigne détaillée
print '<tr class="oddeven"><td><label for="emplacementCD">'.$langs->trans("Emplacement de la consigne détaillée").'</label></td><td>';
print '<textarea name="emplacementCD" id="emplacementCD" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION ? $conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION : '').'</textarea></td></tr>'."\n";

print '<tr class="liste_titre"><th class="titlefield">'.$langs->trans("Informations complémentaires de la société").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Description
print '<tr class="oddeven"><td><label for="description">'.$langs->trans("Description").'</label></td><td>';
print '<textarea name="description" id="description" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_SOCIETY_DESCRIPTION ? $conf->global->DIGIRISK_SOCIETY_DESCRIPTION : '').'</textarea></td></tr>'."\n";

// Moyens généraux mis à disposition
print '<tr class="oddeven"><td><label for="moyensgeneraux">'.$langs->trans("Moyens généraux mis à disposition").'</label></td><td>';
print '<textarea name="moyensgeneraux" id="moyensgeneraux" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_GENERAL_MEANS ? $conf->global->DIGIRISK_GENERAL_MEANS : '').'</textarea></td></tr>'."\n";

// Consignes générales
print '<tr class="oddeven"><td><label for="consignesgenerales">'.$langs->trans(" Consignes générales").'</label></td><td>';
print '<textarea name="consignesgenerales" id="consignesgenerales" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_GENERAL_RULES ? $conf->global->DIGIRISK_GENERAL_RULES : '').'</textarea></td></tr>'."\n";

// RI
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Règlement intérieur").'</th><th>'.$langs->trans("").'</th></tr>'."\n";
// Emplacement
print '<tr class="oddeven"><td><label for="emplacementRI">'.$langs->trans("Emplacement").'</label></td><td>';
print '<textarea name="emplacementRI" id="emplacementRI" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_RULES_LOCATION ? $conf->global->DIGIRISK_RULES_LOCATION : '').'</textarea></td></tr>'."\n";

// DU
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Document Unique").'</th><th>'.$langs->trans("").'</th></tr>'."\n";
// Emplacement
print '<tr class="oddeven"><td><label for="emplacementDU">'.$langs->trans("Emplacement").'</label></td><td>';
print '<textarea name="emplacementDU" id="emplacementDU" class="minwidth300" rows="'.ROWS_3.'">'.($conf->global->DIGIRISK_DUER_LOCATION ? $conf->global->DIGIRISK_DUER_LOCATION : '').'</textarea></td></tr>'."\n";

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

llxFooter();
$db->close();
