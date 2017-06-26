<?php 
namespace Common;

interface CRUDEditModel {
	function load($id);
	function create();
	function validate(array $data);
	function save(array $data);
	function afterSave();
	function edit();
}