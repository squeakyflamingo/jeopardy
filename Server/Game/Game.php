<?php

namespace Game;

use Api\API;
use TemplateRenderer\TemplateRenderer;
use TemplateRenderer\TemplateService;

/**
 * Class Game
 * @package Game
 */
class Game
{
    /**
     * @var TemplateRenderer
     */
    private $templateRenderer;

    /**
     * @var GamefileManager
     */
    private $gamefileManager;

    /**
     * @var
     */
    private $gametitle;

    /**
     * @var
     */
    private $categories;

    /**
     * Game constructor.
     */
    public function __construct()
    {
        $this->templateRenderer = new TemplateRenderer(__DIR__ . '/templates');
        $this->gamefileManager = new GamefileManager('../Spiele');
    }

    /**
     * @return string
     */
    public function start(): string
    {
        if (!empty($_POST)) {
            $team1 = [];
            $team2 = [];

            $team1['name'] = $_POST['team1name'] != '' ? $_POST['team1name'] : 'Team 1';
            $team2['name'] = $_POST['team2name'] != '' ? $_POST['team2name'] : 'Team 2';

            $team1['color'] = $_POST['team1color'];
            $team2['color'] = $_POST['team2color'];

            $team1['textcolor'] = TemplateService::getContrastColor($team1['color']);
            $team2['textcolor'] = TemplateService::getContrastColor($team2['color']);

            $GLOBALS['teams'][1] = $team1;
            $GLOBALS['teams'][2] = $team2;

            $api = new API();
            $api->setConfigForClients($GLOBALS['teams']);

            $gamearray = $this->gamefileManager->getArrayFromGamefile((string) $_POST['gamefile']);
            $this->gametitle = $gamearray['Spieltitel'];
            $this->categories = $gamearray['Kategorie'];

            return $this->renderGame();
        }

        return $this->renderGameConfiguratorForm();
    }

    /**
     * @return string
     */
    private function renderGameConfiguratorForm(): string
    {
        $validFilenames = [];
        $filenames = $this->gamefileManager->getGamefileNames();

        foreach ($filenames as $filename) {
            $filename = substr($filename, 0, -4);
            if($this->gamefileManager->validateXml($filename)) {
                $validFilenames[] = $filename;
            }
        }

        return $this->templateRenderer->renderTemplate('gameConfigurator', [
            'filenames' => $validFilenames
        ]);
    }

    /**
     * @return string
     */
    private function renderGame(): string
    {
        return $this->templateRenderer->renderTemplate('game', [
            'gametitle' => $this->gametitle,
            'categoryColumns' => $this->renderCategoryColumns(),
            'overlayAnswersAndQuestions' => $this->renderOverlayAnswersAndQuestions(),
        ]);
    }

    /**
     * @return array
     */
    private function renderCategoryColumns(): array
    {
        $categoryNumber = 1;
        $categoryColumns = [];

        foreach ($this->categories as $category) {
            $categoryColumns[] = $this->templateRenderer->renderTemplate('categoryColumn', [
                'categorytitle' => $this->getCategorytitleFromCategory($category),
                'valueFields' => $this->renderValueFields($categoryNumber, $category),
            ]);
            $categoryNumber++;
        }

        return $categoryColumns;
    }

    /**
     * @param int $categoryNumber
     * @param array $category
     * @return array
     */
    private function renderValueFields(int $categoryNumber, array $category): array
    {
        $taskNumber = 1;
        $valueFields = [];

        foreach ($this->getTasksFromCategory($category) as $task) {
            $valueFields[] = $this->templateRenderer->renderTemplate('valueField', [
                'category' => $categoryNumber,
                'task' => $taskNumber,
                'value' => $this->getValueFromTask($task),
            ]);
            $taskNumber++;
        }

        return $valueFields;
    }

    /**
     * @return array
     */
    private function renderOverlayAnswersAndQuestions(): array
    {
        $categoryNumber = 1;
        $overlayAnswersAndQuestions = [];

        foreach ($this->categories as $category) {
            $taskNumber = 1;

            foreach ($this->getTasksFromCategory($category) as $task) {
                $overlayAnswersAndQuestions[] = $this->templateRenderer->renderTemplate('overlayAnswerAndQuestion', [
                    'category' => $categoryNumber,
                    'task' => $taskNumber,
                    'answer' => $this->getAnswerFromTask($task),
                    'question' => $this->getQuestionFromTask($task),
                    'value' => $this->getValueFromTask($task),
                ]);

                $taskNumber++;
            }

            $categoryNumber++;
        }

        return $overlayAnswersAndQuestions;
    }

    /**
     * @param array $category
     * @return string
     */
    private function getCategorytitleFromCategory(array $category): string
    {
        return $category['Kategorietitel'];
    }

    /**
     * @param array $category
     * @return array
     */
    private function getTasksFromCategory(array $category): array
    {
        return $category['Aufgabe'];
    }

    /**
     * @param array $task
     * @return string
     */
    private function getQuestionFromTask(array $task): string
    {
        return $task['Fragestellung'];
    }

    /**
     * @param array $task
     * @return string
     */
    private function getAnswerFromTask(array $task): string
    {
        return $task['Antwort'];
    }

    /**
     * @param array $task
     * @return string
     */
    private function getValueFromTask(array $task): string
    {
        return $task['Wert'];
    }
}
