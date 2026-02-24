<?php
declare(strict_types=1);

final class PublicController extends BaseController
{
    public function home(): void
    {
        $this->redirect('/public/index.html');
    }

    public function about(): void
    {
        $this->redirect('/public/about.html');
    }

    public function howItWorks(): void
    {
        $this->redirect('/public/how-it-works.html');
    }

    public function features(): void
    {
        $this->redirect('/public/features.html');
    }

    public function tamilRelationshipSystem(): void
    {
        $this->redirect('/public/tamil-relationship-system.html');
    }

    public function contact(): void
    {
        $this->redirect('/public/contact.html');
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
