<?php
namespace Services\Email\Providers;

use Services\Email\EmailServiceInterface;
use Libraries\Cache\CacheManager;
use Externals\PHPMailer\PHPMailer;
use Externals\PHPMailer\Exception;

class PHPMailerService implements EmailServiceInterface {
    private $config;
    private $cacheManager;
    private $db;
    private $configLoaded = false;
    private $configName;
    private $mailer;
    
    /**
     * Constructor
     * 
     * @param string $configName Nombre de la configuración a utilizar
     */
    public function __construct(string $configName = 'default') {
        $this->configName = $configName;
        $this->cacheManager = CacheManager::getInstance();
        $this->db = new \Mysql();
        $this->loadConfig();
    }
    
    /**
     * Cargar la configuración
     * 
     * @return bool Éxito de la carga
     */
    private function loadConfig(): bool {
        // Intentar obtener de la caché
        $cacheKey = 'email_config_' . $this->configName;
        
        $this->config = $this->cacheManager->remember($cacheKey, function() {
            // Si no está en caché, consultar a la BD
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_get_email_config',
                [$this->configName],
                []
            );
            
            if (empty($result) || empty($result['results'])) {
                return null;
            }
            
            return $result['results'][0];
        }, 604800); // 7 días
        
        $this->configLoaded = ($this->config !== null);
        return $this->configLoaded;
    }
    
    /**
     * Inicializar PHPMailer
     * 
     * @return PHPMailer Instancia configurada
     */
    private function initMailer(): PHPMailer {
        $mailer = new PHPMailer(true);
        
        // Configuración del servidor
        $mailer->isSMTP();
        $mailer->Host = $this->config['host'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $this->config['username'];
        $mailer->Password = $this->config['password'];
        $mailer->Port = $this->config['port'];
        
        // Configuración de seguridad
        if ($this->config['secure'] === 'tls') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($this->config['secure'] === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        // Configuración del remitente
        $mailer->setFrom($this->config['from_email'], $this->config['from_name']);
        
        // Configuración general
        $mailer->CharSet = 'UTF-8';
        $mailer->isHTML(true);
        
        return $mailer;
    }
    
    /**
     * {@inheritdoc}
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $mailer = $this->initMailer();
            
            // Destinatario
            $mailer->addAddress($to);
            
            // CC y BCC
            if (isset($options['cc']) && is_array($options['cc'])) {
                foreach ($options['cc'] as $cc) {
                    $mailer->addCC($cc);
                }
            }
            
            if (isset($options['bcc']) && is_array($options['bcc'])) {
                foreach ($options['bcc'] as $bcc) {
                    $mailer->addBCC($bcc);
                }
            }
            
            // Adjuntos
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment) && isset($attachment['path'])) {
                        $name = $attachment['name'] ?? basename($attachment['path']);
                        $mailer->addAttachment($attachment['path'], $name);
                    } else if (is_string($attachment) && file_exists($attachment)) {
                        $mailer->addAttachment($attachment);
                    }
                }
            }
            
            // Asunto y cuerpo
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            
            // Versión sin HTML
            if (isset($options['alt_body'])) {
                $mailer->AltBody = $options['alt_body'];
            } else {
                //$mailer->AltBody = strip_tags($body);
            }
            
            // Enviar
            return $mailer->send();
            
        } catch (Exception $e) {
            error_log("Error al enviar correo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function sendTemplate(string $to, string $subject, string $template, array $data = [], array $options = []): bool {
        if (!$this->isConfigured()) {
            return false;
        }
        
        // Renderizar la plantilla
        ob_start();
        $empresa = $this->config['from_name'];
        $remitente = $this->config['from_email'];
        extract($data);
        require_once("Views/Template/Email/" . $template . ".php");
        $body = ob_get_clean();
        
        // Enviar el correo con el cuerpo generado
        return $this->send($to, $subject, $body, $options);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool {
        return $this->configLoaded;
    }
}