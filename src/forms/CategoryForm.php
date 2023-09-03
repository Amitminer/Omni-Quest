<?php

namespace AmitxD\OmniQuest\forms;

use AmitxD\OmniQuest\{
	OmniQuest,
	Manager\QuestManager
};

use AmitxD\OmniQuest\libs\FormAPI\SimpleForm;

class CategoryForm extends SimpleForm
{

	public function __construct(?callable $callable){
		parent::__construct($callable);
		$categories = QuestManager::getCategoriesName();
		foreach ($categories as $category) {
			$this->addButton($category, 0);
		}
		if(!is_null(OmniQuest::getInstance()->getQuestConfig()->get("category-select-title"))){
			$this->setTitle(OmniQuest::getInstance()->getQuestConfig()->get("category-select-title"));
		}
		if(!is_null(OmniQuest::getInstance()->getQuestConfig()->get("category-select-content"))){
			$this->setContent(OmniQuest::getInstance()->getQuestConfig()->get("category-select-content"));
		}
	}
}