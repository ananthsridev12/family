<?php
declare(strict_types=1);

final class PublicController extends BaseController
{
    public function home(): void
    {
        $this->render('public/home', ['title' => 'Home']);
    }

    public function about(): void
    {
        $this->render('public/about', ['title' => 'About']);
    }

    public function howItWorks(): void
    {
        $this->render('public/how_it_works', ['title' => 'How It Works']);
    }

    public function features(): void
    {
        $this->render('public/features', ['title' => 'Features']);
    }

    public function tamilRelationshipSystem(): void
    {
        $this->render('public/tamil_relationship_system', ['title' => 'Tamil Relationship System']);
    }

    public function contact(): void
    {
        $this->render('public/contact', ['title' => 'Contact']);
    }
}