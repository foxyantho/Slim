<?php
namespace Slim;

class NotFoundHandler
{
    protected $app;

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
    }

    public function __invoke()
    {
        $this->app['response']->setStatus(404);
        $this->app['response']->setHeader('Content-type', 'text/html');

        return $this->generateTemplateMarkup(
            '404 Page Not Found',
            '<p>The page you are looking for could not be found. Check the address bar to ensure your URL is spelled ' .
            'correctly. If all else fails, you can visit our home page at the link below.</p><a href="' .
            $this->app['request']->getScriptName() . '/">Visit the Home Page</a>'
        );
    }

    /**
     * Generate diagnostic template markup
     *
     * This method accepts a title and body content to generate an HTML document layout.
     *
     * @param  string $title The title of the HTML template
     * @param  string $body  The body content of the HTML template
     * @return string
     */
    protected function generateTemplateMarkup($title, $body)
    {
        return sprintf(
            "<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;" .
            "width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $body
        );
    }
}
