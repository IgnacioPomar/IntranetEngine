<?php
require_once $GLOBALS ['basePath'] . 'src/ColumnFormatter.php';
require_once $GLOBALS ['basePath'] . 'src/AutoForm.php';

class MaintenanceUsers extends Plugin
{
	private $jsonFile;


	public function __construct (Context $context)
	{
		parent::__construct ($context);

		$this->jsonFile = $GLOBALS ['basePath'] . 'src/tables/users.jsonTable';
	}

	// @formatter:off
	const COLS_TABLE_USERS = array (
			'name'		=> array ('w-400', 'User name', 'Username visible on the platform.'),
			'email'		=> array ('w-400', 'Email', 'Access email.'),
			'groups'	=> array ('w-400', 'Groups', 'Groups of the user.'),
			'isActive'	=> array ('w-50 text-center', 'Active', 'Indicates if the user can access the platform.'),
			'isAdmin'	=> array ('w-50 text-center', 'Admin', 'Indicates if the user has administrator access.'),
	);
	// @formatter:on

	/**
	 *
	 * @return string
	 */
	private function showListUsers ()
	{
		$query = $this->getQueryUsers ();
		$resultUsers = $this->context->mysqli->query ($query);

		$formatter = new ColumnFormatter (self::COLS_TABLE_USERS);
		$formatter->stylers ['isActive'] = new FormatterColumnToCheckbox ();
		$formatter->stylers ['isAdmin'] = new FormatterColumnToCheckbox ();

		$retVal = "<a href='{$this->uriPrefix}newUser' class='btn mb-3'>Add new user</a>";
		$retVal .= "<div class='head'>{$formatter->getHeaderCols ()}</div>";

		while ($user = $resultUsers->fetch_assoc ())
		{
			$retVal .= '<div class="line">';

			$retVal .= $formatter->getStyledBodyCols ($user);

			$retVal .= '<div class="w-100"></div>';

			$link = "{$this->uriPrefix}&idUser={$user['idUser']}";
			$retVal .= "<a href='$link'><span class='w-50'><svg xmlns='http: // www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-fill' viewBox='0 0 16 16'><path d='M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z'/></svg></span></a>";

			$retVal .= "</div>";
		}

		return $retVal;
	}


	/**
	 *
	 * @param number $idUser
	 * @return string
	 */
	private function getQueryUsers ($idUser = 0)
	{
		// YAGNI: Receive an array with the necessary columns
		$query = 'SELECT u.*,g.groups  FROM weUsers u ';
		$query .= ' INNER JOIN  (SELECT GROUP_CONCAT(grpName  SEPARATOR ", ") AS groups, rel.idUser FROM weGroups g INNER JOIN weUsersGroups rel ON g.idGrp = rel.idGrp GROUP BY rel.idUser) g';
		$query .= ' ON u.idUser=g.idUser';
		if ($idUser != 0)
		{
			$query .= " WHERE idUser = $idUser";
		}
		return $query;
	}


	/**
	 *
	 * @return string
	 */
	private function showUserForm ()
	{
		$autoForm = new AutoForm ($this->jsonFile);

		// TODO: Fix the separate manifold...
		$fieldSet = array ('email', 'name', 'password', 'isActive', 'isAdmin');
		// It is necessary to generate the form
		$autoForm->set = $fieldSet;
		$retVal = '';

		if (! empty ($_POST))
		{
			$this->prepareDataUser ();

			if (! empty ($_POST ['idUser']))
			{
				$retVal .= $this->updateUser ($autoForm);
			}
			else if (! empty ($_POST ['email']))
			{
				// YAGNI: Check if there is already a user with that email
				return $this->insertNewUser ($autoForm);
			}
		}

		$this->addMultiselectGroup ($autoForm, ! empty ($_POST ['idUser']));

		if (! empty ($_GET ['idUser']))
		{
			$query = $this->getQueryUsers ($_GET ['idUser']);
			if ($resultUser = $this->context->mysqli->query ($query))
			{
				if ($user = $resultUser->fetch_assoc ())
				{
					$autoForm->setHidden ('idUser', $user ['idUser']);
					$user ['password'] = '';

					$retVal .= '<div class="container">';
					if (empty ($retVal))
					{
						$retVal .= '<h1>Edit user</h1>';
					}
					$retVal .= $autoForm->generateForm ($user, ! empty ($_POST ['idUser']));
					$retVal .= '</div>';

					return $retVal;
				}
			}
		}

		$retVal = '<div class="container"><h1>Add new user</h1>';
		$retVal .= $autoForm->generateForm ($_POST);
		$retVal .= '</div>';

		return $retVal;
	}


	private function addMultiselectGroup (&$autoForm, $isDisabled = true)
	{
		// A lo mejor meterlos en una variable de la clase
		$groups = $this->getGroups ();

		$layout = '<div class="field d-flex"><label>Groups</label>';
		$layout .= '<div class="d-flex flex-column fw-normal">';
		foreach ($groups as $group)
		{
			$layout .= "<div><label for='{$group['idGrp']}'>{$group ['grpName']}</label>";
			$checked = ($group ['withIt']) ? 'checked' : '';
			$disabled = $isDisabled ? 'disabled' : '';
			$layout .= "<input type='checkbox' id='{$group['idGrp']}' value='{$group['idGrp']}' name='groups[]' $checked $disabled></div>";
		}
		$layout .= '</div></div>';

		$autoForm->externalFooterHTML = $layout;
	}


	private function prepareDataUser ()
	{
		// TODO: Think about how to remove this for checkbox type data
		$_POST ['isActive'] = $_POST ['isActive'] ?? 0;
		$_POST ['isAdmin'] = $_POST ['isAdmin'] ?? 0;

		if (! empty ($_POST ['password']))
		{
			$_POST ['password'] = password_hash ($_POST ['password'], PASSWORD_DEFAULT);
		}
		else
		{
			unset ($_POST ['password']);
		}
	}


	private function getGroups ()
	{
		$query = array ();
		$query [] = 'SELECT g.*, !ISNULL(u.idUser) AS withIt FROM weGroups g';
		$query [] = 'LEFT JOIN   weUsersGroups  u ON g.idGrp = u.idGrp AND  idUser=' . ($_GET ['idUser'] ?? 0);
		$resultGroups = $this->context->mysqli->query (join (' ', $query));

		return $resultGroups->fetch_all (MYSQLI_ASSOC);
	}


	/**
	 *
	 * @param object $autoForm
	 * @return string
	 */
	private function insertNewUser ($autoForm)
	{
		$autoForm->mysqli = $this->context->mysqli;
		$query = $autoForm->getInsertSql ([ ]);
		$this->context->mysqli->query ($query);

		$this->updateTableUsersGroups ($this->context->mysqli->insert_id);

		$retVal = '<p>Registro Insertado Correctamente</p>';
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/></svg>';
		$retVal .= '<div class="container"><a class="btn rigth" href="' . strtok ($this->uriPrefix, '&') . '">' . $icon . 'Volver</a></div>';
		return $retVal;
	}


	/**
	 *
	 * @param object $autoForm
	 * @return string
	 */
	private function updateUser ($autoForm)
	{
		$autoForm->mysqli = $this->context->mysqli;
		$sql = $autoForm->getUpdateSql ([ ], [ 'idUser']);
		$this->context->mysqli->query ($sql);

		$this->updateTableUsersGroups ($_POST ['idUser']);

		$retVal = "<p>Registro Actualizado Correctamente</p>";
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/></svg>';
		$retVal .= '<div class="container"><a class="btn rigth" href="' . strtok ($this->uriPrefix, '&') . '">' . $icon . 'Volver</a></div>';

		return $retVal;
	}


	private function updateTableUsersGroups ($idUser)
	{
		$query = "DELETE FROM weUsersGroups WHERE idUser = $idUser";
		$this->context->mysqli->query ($query);

		if (! empty ($_POST ['groups']))
		{
			foreach ($_POST ['groups'] as $idGrp)
			{
				$query = "INSERT INTO weUsersGroups (idUser, idGrp) VALUES ($idUser,$idGrp)";
				$this->context->mysqli->query ($query);
			}
		}
	}


	public static function getPlgInfo (): array
	{
	}


	/**
	 *
	 * @return string
	 */
	public function main ()
	{
		if (isset ($_GET ['newUser']) || ! empty ($_GET ['idUser']))
		{
			$retVal = $this->showUserForm ();
		}
		else
		{
			$retVal = $this->showListUsers ();
		}

		return $retVal;
	}
}

// YAGNI: Include it in the ColumnFormatter class
class FormatterColumnToCheckbox
{


	/**
	 *
	 * @param mixed $val
	 * @param string $class
	 * @return string
	 */
	public function getSpan ($val, $class)
	{
		$retVal = "<div class='$class'>";

		$attributes = [ 'disabled'];
		if ($val)
		{
			$attributes [] = 'checked';
		}

		$retVal .= '<input type="checkbox"' . join (' ', $attributes) . '>';
		$retVal .= '</div>';
		return $retVal;
	}
}

