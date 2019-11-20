<?php
include_once 'rbac.php';
include_once ('entidades/portadaNotas.php');
include_once ('entidades/usuario.php');
/**
 * Esta clase es un plugin "estatico" responsable de enseñar la portada del sitio web
 */
class Portada extends Plugin
{


	private function showPortada (array $templates)
	{
		$retVal = '';
		return $retVal;
	}

	public static function getExternalCss ()
	{
		$css = array ();
		$css [] = 'portada.css';
		$css [] = '../../../vendor/select2/dist/css/select2.min.css';

		return $css;
	}


	public static function getExternalJs ()
	{
		$js = array ();
		$js [] = 'vendor/select2/dist/js/select2.min.js';

		return $js;
	}


	public static function getPlgInfo ()
	{
		$plgInfo = array ();
		$plgInfo ['plgName'] = 'Portada';
		$plgInfo ['plgDescription'] = 'PLugin estatico que siempre va incluido que muestra la portada del sitio';
		$plgInfo ['isMenu'] = 0;
		$plgInfo ['isMenuAdmin'] = 0;
		$plgInfo ['isSkinable'] = 1;

		return $plgInfo;
	}


	/**
	 *
	 * Llamamos a CrudModel para crear o actualizar las tablas desde las entidades
	 *
	 * @param mysqli $mysqli
	 *        	Conexión al servidor mysql
	 */
	public static function updateTables ($mysqli)
	{
		$portadaNotas = new PortadaNotas ();
		$msg = CrudModel::createOrUpdateTable ( $portadaNotas, $mysqli );

		return $msg;
	}


	/**
	 *
	 * @param mysqli $mysqli
	 */
	public static function main ($mysqli)
	{
		$portada = new Portada ( $mysqli );

		$templates = array ();
		if (isset ( $_SESSION ['userId'] ))
		{
			$templates = explode ( ",", RBAC::getUserTemplates ( $mysqli ) );
		}

		if (isset ( $_GET ['action'] ))
		{
			switch ($_GET ['action'])
			{
				case 'add':
					return $portada->addNewNote ();
					break;
				case 'remove':
					break;
				case 'edit':
					break;
			}
		}
		else
		{
			return $portada->showPortada ( $templates );
		}
	}
}