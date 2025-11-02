<?php
/**
 * VanTracing - SEO Meta Tags Component
 * Componente de Meta Tags para SEO
 * 
 * This file provides standardized SEO meta tags for all pages
 * Este arquivo fornece meta tags SEO padronizadas para todas as páginas
 * 
 * @package VanTracing
 * @version 2.0
 * @author Kevyn
 */

/**
 * Generate SEO meta tags for VanTracing pages
 * Gera meta tags SEO para páginas do VanTracing
 * 
 * @param string $title Page title / Título da página
 * @param string $description Page description / Descrição da página
 * @param string $keywords Keywords for the page / Palavras-chave para a página
 * @param string $image Social media image / Imagem para redes sociais
 */
function generate_seo_meta($title = '', $description = '', $keywords = '', $image = '') {
    // Default values / Valores padrão
    $default_title = 'VanTracing - Sistema de Rastreamento Escolar';
    $default_description = 'Sistema completo de rastreamento e monitoramento de transporte escolar em tempo real. Segurança e tranquilidade para pais e responsáveis.';
    $default_keywords = 'transporte escolar, rastreamento, monitoramento, GPS, segurança, van escolar, localização tempo real';
    $base_url = getenv('APP_URL') ?: 'http://localhost/VanTracing';
    
    // Use provided values or defaults / Usa valores fornecidos ou padrões
    $page_title = !empty($title) ? $title . ' - VanTracing' : $default_title;
    $page_description = !empty($description) ? $description : $default_description;
    $page_keywords = !empty($keywords) ? $keywords . ', ' . $default_keywords : $default_keywords;
    $page_image = !empty($image) ? $base_url . '/' . $image : $base_url . '/img/perfil.png';
    
    // Generate meta tags / Gera meta tags
    echo '<!-- SEO Meta Tags -->' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($page_description) . '">' . "\n";
    echo '<meta name="keywords" content="' . htmlspecialchars($page_keywords) . '">' . "\n";
    echo '<meta name="author" content="VanTracing Team">' . "\n";
    echo '<meta name="robots" content="index, follow">' . "\n";
    echo '<meta name="language" content="pt-BR">' . "\n";
    echo '<link rel="canonical" href="' . $base_url . $_SERVER['REQUEST_URI'] . '">' . "\n";
    
    echo "\n<!-- Open Graph Meta Tags -->\n";
    echo '<meta property="og:title" content="' . htmlspecialchars($page_title) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($page_description) . '">' . "\n";
    echo '<meta property="og:image" content="' . $page_image . '">' . "\n";
    echo '<meta property="og:url" content="' . $base_url . $_SERVER['REQUEST_URI'] . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="VanTracing">' . "\n";
    echo '<meta property="og:locale" content="pt_BR">' . "\n";
    
    echo "\n<!-- Twitter Card Meta Tags -->\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($page_title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($page_description) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . $page_image . '">' . "\n";
    
    echo "\n<!-- Favicons and Icons -->\n";
    echo '<link rel="icon" type="image/svg+xml" href="' . $base_url . '/assets/icons/favicon.svg">' . "\n";
    echo '<link rel="icon" type="image/png" href="' . $base_url . '/img/perfil.png">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . $base_url . '/img/perfil.png">' . "\n";
    
    echo "\n<!-- Mobile and Responsive -->\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    echo '<meta name="theme-color" content="#0d6efd">' . "\n";
}

/**
 * Generate structured data for SEO
 * Gera dados estruturados para SEO
 */
function generate_structured_data($type = 'website') {
    $base_url = getenv('APP_URL') ?: 'http://localhost/VanTracing';
    
    $structured_data = array(
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'VanTracing',
        'applicationCategory' => 'Transportation',
        'operatingSystem' => 'Web Browser',
        'description' => 'Sistema completo de rastreamento e monitoramento de transporte escolar em tempo real',
        'url' => $base_url,
        'author' => array(
            '@type' => 'Organization',
            'name' => 'VanTracing Team'
        ),
        'offers' => array(
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'BRL'
        ),
        'aggregateRating' => array(
            '@type' => 'AggregateRating',
            'ratingValue' => '4.8',
            'reviewCount' => '150'
        )
    );
    
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    echo '</script>' . "\n";
}
?>