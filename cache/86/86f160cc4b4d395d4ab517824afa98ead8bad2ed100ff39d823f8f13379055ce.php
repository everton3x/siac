<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* report/prog-orc/analitic.html.twig */
class __TwigTemplate_273a31ba9d497b649917e7332d22dc61afe12244096f2dcfbab357e01960915e extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!DOCTYPE html>
<html>
    <head>
        <title>Avaliação da programação orçamentária</title>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    </head>
    <body>
        <header>
            <div class=\"ui medium header\">
                Avaliação da Programação Orçamentária e Financeira
                <div class=\"ui sub header\">Data-base: ";
        // line 12
        echo twig_escape_filter($this->env, twig_date_format_filter($this->env, ($context["dataBase"] ?? null), "d/m/Y"), "html", null, true);
        echo "</div>
            </div>
            <div class=\"ui segments\">
                <div class=\"ui segment\">
                    <div class=\"header\">Entidades incluídas:</div>
                </div>
                <div class=\"ui segment\">
                    <div class=\"ui list\">
                        ";
        // line 20
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["entidades"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["entidade"]) {
            // line 21
            echo "                            <div class=\"item\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["entidade"], "nome", [], "any", false, false, false, 21), "html", null, true);
            echo "</div>
                        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['entidade'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 23
        echo "                    </div>
                </div>
            </div>
        </header>
    </body>
</html>
";
    }

    public function getTemplateName()
    {
        return "report/prog-orc/analitic.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  74 => 23,  65 => 21,  61 => 20,  50 => 12,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "report/prog-orc/analitic.html.twig", "C:\\Users\\everton.INDEPENDENCIA\\Documents\\NetBeansProjects\\siac\\tpl\\report\\prog-orc\\analitic.html.twig");
    }
}
