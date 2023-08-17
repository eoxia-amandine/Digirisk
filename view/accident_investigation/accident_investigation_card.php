<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 *  \file       view/accident_investigation.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Tab of accident investigation on generic element
 */

// Load EasyCRM environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

$taskRefClass = $conf->global->PROJECT_TASK_ADDON;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/task/' . $taskRefClass . '.php';

// Load Digirisk librairies
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/accident_investigation.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident_investigation.lib.php';

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$id          = GETPOST('id', 'int');
$fkAccident  = GETPOST('fk_accident', 'int');
$objectType  = GETPOST('from_type', 'alpha');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accidentinvestigationcard'; // To manage different context of search
$cancel      = GETPOST('cancel', 'aZ09');
$backtopage  = GETPOST('backtopage', 'alpha') ? GETPOST('backtopage', 'alpha') : 'accident_investigation_list.php';

// Initialize technical objects
$accident   = new Accident($db);
$object     = new AccidentInvestigation($db);
$project    = new Project($db);
$task       = new Task($db);
$refTaskMod = new $taskRefClass();

// Initialize view objects
$form        = new Form($db);
$formcompany = new FormCompany($db);

$hookmanager->initHooks(['accidentinvestigation', 'accidentinvestigationcard', 'digiriskdolibarrglobal', 'globalcard']); // Note that conf->hooks_modules contains array

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

$uploadDir = $conf->digiriskdolibarr->multidir_output[$object->entity ?? 1];

// Load object
require_once DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be included, not include_once.

// Security check - Protection if external user
$permissiontoread   = $user->rights->digiriskdolibarr->accident_investigation->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident_investigation->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident_investigation->delete;
saturne_check_access($permissiontoread);

/*
*  Actions
*/

$parameters = ['id' => $id];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $objectLinked may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if ($cancel && !empty($backtopage)) {
		header('Location: ' . ($action == 'update' ? $_SERVER['PHP_SELF'] . '?id=' . $id : $backtopage));
		exit;
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions builddoc, forcebuilddoc, remove_file.
	require_once __DIR__ . '/../../../saturne/core/tpl/documents/documents_action.tpl.php';

}

/*
*	View
*/

$title   = $langs->trans('AccidentInvestigation');
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

if ($conf->browser->layout == 'phone') {
	$onPhone = 1;
} else {
	$onPhone = 0;
}

saturne_header(0,'', $title, $helpUrl);

if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewAccidentInvestigation"), '', $object->picto);

	print dol_get_fiche_head();

	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}

	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	print '</table></br>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Create');
} else if ($id > 0 && $action == 'edit') {
	print load_fiche_titre($langs->trans("UpdateAccidentInvestigation"), '', $object->picto);

	print dol_get_fiche_head();

	print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}

	print '<table class="border centpercent tableforfieldupdate">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	print '</table></br>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('Update');
} else if ($id > 0 || (!empty($ref) && empty($action))) {
	$object->fetch($id);

	saturne_get_fiche_head($object, 'accidentinvestigation', $title);
	saturne_banner_tab($object, 'ref', '', 1, 'ref', 'ref', '', !empty($object->photo));

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';

	require_once DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Categories.
	if (isModEnabled('categorie')) {
		print '<tr><td class="valignmiddle">' . $langs->trans('Categories') . '</td><td>';
		print $form->showCategories($object->id, $object->element, 1);
		print '</td></tr>';
	}

	print '</table></div>';

	print '<div class="clearboth"></div>';

	if ($action != 'presend') {
		print '<div class="tabsAction">';

		// Edit
		$displayButton = $onPhone ? '<i class="fas fa-pencil-alt fa-2x"></i>' : '<i class="fas fa-pencil-alt"></i>' . ' ' . $langs->trans('Update');
		if ($object->status < $object::STATUS_LOCKED) {
			print '<a class="butAction" id="actionButtonEdit" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit' . '">' . $displayButton . '</a>';
		} else {
			print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans('AccidentInvestigationMustBeOpen')) . '">' . $displayButton . '</span>';
		}

		print '</div>';

		print '<div class="fichecenter"><div class="fichehalfleft">';

		// Documents.
		$objRef    = dol_sanitizeFileName($object->ref);
		$dirFiles  = $object->element . 'document/' . $objRef;
		$fileDir   = $uploadDir . '/' . $dirFiles;
		$urlSource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;

		print saturne_show_documents('digiriskdolibarr:AccidentInvestigationDocument', $dirFiles, $fileDir, $urlSource, $permissiontoadd, $permissiontodelete, $conf->global->DIGIRISKDOLIBARR_ACCIDENTINVESTIGATION_DOCUMENT_DEFAULT_MODEL, 1, 0, 0, 0, 0, '', '', '', $langs->defaultlang, $object, 0, 'remove_file', empty(dol_dir_list($fileDir)), $langs->trans('ObjectMustBeLockedToGenerate', ucfirst($langs->transnoentities('The' . ucfirst($object->element)))));

		print '</div><div class="fichehalfright">';

		// List of actions on element.
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formActions = new FormActions($db);
		$formActions->showactions($object, $object->element . '@' . $object->module, 0, 1, '', 10);

		print '</div></div>';
	}
}

// End of page
llxFooter();
$db->close();
