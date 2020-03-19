<?php
namespace Suilven\CricketSite\DashboardPanel;

use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Suilven\CricketSite\Model\MatchReport;
use UncleCheese\Dashboard\DashboardPanel;

class BlogPostOrMatchReportPanel extends DashboardPanel {

    private static $db = [
        'Amount' => 'Int'
    ];

    private static $defaults = [
        'Amount' => 100
    ];

    public function getLabel() {
        return 'Find Match Reports That Are Blog Posts';
    }


    public function getDescription() {
        return '';
    }


    public function getConfiguration() {
        $fields = parent::getConfiguration();
        $fields->push(TextField::create('Amount', 'Number of posts to show'));
        return $fields;
    }



    public function BlogPosts() {
        return BlogPost::get()->sort('PublishDate', 'Desc')->limit($this->Amount);
    }
}
