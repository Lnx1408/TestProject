<?php
/**
 * Template para el modal del generador de requisitos con IA
 * Ubicación: Views/Template/Generators/requirements_generator_modal.php
 */
?>
<div class="ai-generator-modal">
  <div class="modal-container">
    <!-- Panel lateral con stepper vertical -->
    <div class="stepper-container">
      <div class="stepper-title">
        <i class="ri-ai-generate"></i> <?= $data['generator_title'] ?? 'Generador IA' ?>
      </div>
      <div class="stepper-steps">
        <!-- Paso 1: Configuración -->
        <div class="step-item active" data-step="1">
          <div class="step-circle">
            <span>1</span>
          </div>
          <div class="step-content">
            <div class="step-label" data-i18n="requirements_generator.steps.configuration.title">Configuración</div>
            <div class="step-description" data-i18n="requirements_generator.steps.configuration.description">Parámetros para la generación</div>
          </div>
        </div>

        <!-- Paso 2: Visualización y Edición -->
        <div class="step-item" data-step="2">
          <div class="step-circle">
            <span>2</span>
          </div>
          <div class="step-content">
            <div class="step-label" data-i18n="requirements_generator.steps.visualization.title">Visualización</div>
            <div class="step-description" data-i18n="requirements_generator.steps.visualization.description">Revisar y editar resultados</div>
          </div>
        </div>

        <!-- Paso 3: Confirmación -->
        <div class="step-item" data-step="3">
          <div class="step-circle">
            <span>3</span>
          </div>
          <div class="step-content">
            <div class="step-label" data-i18n="requirements_generator.steps.confirmation.title">Confirmación</div>
            <div class="step-description" data-i18n="requirements_generator.steps.confirmation.description">Guardar requisitos generados</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenedor principal para el contenido de cada paso -->
    <div class="step-content-container">
      <!-- PASO 1: CONFIGURACIÓN -->
      <div class="step-content-panel" id="step-panel-1">
        <div class="step-content-header">
          <h2 class="step-content-title" data-i18n="requirements_generator.steps.configuration.header">Configurar generación de requisitos</h2>
          <p class="step-content-subtitle" data-i18n="requirements_generator.steps.configuration.subheader">Defina los parámetros para la generación automática de requisitos con inteligencia artificial.</p>
        </div>

        <form class="config-form">
          <!-- Selección de proveedor IA -->
          <div class="form-group">
            <label data-i18n="requirements_generator.form.provider.label">Seleccione el proveedor IA</label>
            <div class="radio-group" id="ai-provider-options">
              <div class="radio-option selected">
                <input type="radio" name="ai-provider" id="provider-openai" value="openai" checked>
                <i class="radio-icon ri-openai-fill"></i>
                <label class="radio-label" for="provider-openai">
                  OpenAI
                  <span class="radio-sublabel" data-i18n="requirements_generator.form.provider.openai_sublabel">Recomendado</span>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="ai-provider" id="provider-gemini" value="gemini">
                <i class="radio-icon ri-google-fill"></i>
                <label class="radio-label" for="provider-gemini">
                  Gemini
                  <span class="radio-sublabel" data-i18n="requirements_generator.form.provider.gemini_sublabel">Google AI</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Selección de idioma -->
          <div class="form-group">
            <label data-i18n="requirements_generator.form.language.label">Idioma de los requisitos</label>
            <div class="radio-group" id="language-options">
              <div class="radio-option selected">
                <input type="radio" name="language" id="lang-es" value="es" checked>
                <i class="radio-icon ri-global-line"></i>
                <label class="radio-label" for="lang-es">
                  Español
                  <span class="radio-sublabel" data-i18n="requirements_generator.form.language.es_sublabel">Castellano</span>
                </label>
              </div>
              <div class="radio-option">
                <input type="radio" name="language" id="lang-en" value="en">
                <i class="radio-icon ri-global-line"></i>
                <label class="radio-label" for="lang-en">
                  Inglés
                  <span class="radio-sublabel" data-i18n="requirements_generator.form.language.en_sublabel">English</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Campo para el contexto del proyecto -->
          <div class="form-group">
            <label for="context" data-i18n="requirements_generator.form.context.label">Contexto del proyecto</label>
            <textarea 
              id="context" 
              class="form-control" 
              rows="4" 
              placeholder="<?= $data['context_placeholder'] ?? 'Describe brevemente el contexto o dominio del proyecto para el que necesitas generar requisitos.' ?>"
            ></textarea>
          </div>

          <!-- Número de requisitos -->
          <div class="form-group">
            <label for="num-requirements" data-i18n="requirements_generator.form.num_requirements.label">Número de requisitos a generar</label>
            <div class="input-with-range">
              <input 
                type="number" 
                id="num-requirements" 
                class="form-control" 
                min="5" 
                max="20" 
                value="10"
              >
              <input 
                type="range" 
                id="num-range" 
                min="5" 
                max="20" 
                value="10"
              >
            </div>
            <small class="form-hint" data-i18n="requirements_generator.form.num_requirements.hint">Mínimo: 5, Máximo: 20</small>
          </div>
        </form>

        <div class="actions-container">
          <div></div> <!-- Espacio para alinear botones a la derecha -->
          <button id="btn-generate" class="btn btn-primary">
            <i class="ri-magic-line"></i> <span data-i18n="requirements_generator.buttons.generate">Generar requisitos</span>
          </button>
        </div>
      </div>

      <!-- PASO 2: VISUALIZACIÓN Y EDICIÓN -->
      <div class="step-content-panel" id="step-panel-2" style="display: none;">
        <div class="step-content-header">
          <h2 class="step-content-title" data-i18n="requirements_generator.steps.visualization.header">Requisitos generados</h2>
          <p class="step-content-subtitle" data-i18n="requirements_generator.steps.visualization.subheader">Revise los requisitos generados y edítelos si es necesario.</p>
        </div>

        <!-- Estado de carga -->
        <div id="loading-state" class="loading-indicator">
          <div class="spinner"></div>
          <p class="loading-message" data-i18n="requirements_generator.loading.message">Generando requisitos, por favor espere...</p>
        </div>

        <!-- Tabla de requisitos generados (visible después de cargar) -->
        <div id="results-container" style="display: none;">
          <div class="requirements-controls">
            <button id="btn-regenerate" class="btn btn-secondary">
              <i class="ri-refresh-line"></i> <span data-i18n="requirements_generator.buttons.regenerate">Regenerar</span>
            </button>
            <div class="requirements-stats">
              <span id="requirements-count">0</span> <span data-i18n="requirements_generator.table.count_label">requisitos generados</span>
            </div>
          </div>

          <div id="requirements-table"></div>
        </div>

        <div class="actions-container">
          <button id="btn-back-step1" class="btn btn-secondary">
            <i class="ri-arrow-left-line"></i> <span data-i18n="requirements_generator.buttons.back">Volver</span>
          </button>
          <button id="btn-to-step3" class="btn btn-primary">
            <span data-i18n="requirements_generator.buttons.continue">Continuar</span> <i class="ri-arrow-right-line"></i>
          </button>
        </div>
      </div>

      <!-- PASO 3: CONFIRMACIÓN -->
      <div class="step-content-panel" id="step-panel-3" style="display: none;">
        <div class="step-content-header">
          <h2 class="step-content-title" data-i18n="requirements_generator.steps.confirmation.header">Confirmar y guardar</h2>
          <p class="step-content-subtitle" data-i18n="requirements_generator.steps.confirmation.subheader">Revise el resumen y guarde los requisitos generados.</p>
        </div>

        <div class="summary-container">
          <div class="summary-item">
            <div class="summary-label" data-i18n="requirements_generator.summary.provider">Proveedor IA:</div>
            <div class="summary-value" id="summary-provider">OpenAI</div>
          </div>
          <div class="summary-item">
            <div class="summary-label" data-i18n="requirements_generator.summary.language">Idioma:</div>
            <div class="summary-value" id="summary-language">Español</div>
          </div>
          <div class="summary-item">
            <div class="summary-label" data-i18n="requirements_generator.summary.context">Contexto:</div>
            <div class="summary-value" id="summary-context"></div>
          </div>
        </div>

        <div class="requirements-summary">
          <div class="requirements-count" id="summary-count">0 requisitos generados</div>
          <div class="requirements-distribution">
            <div class="distribution-item">
              <span class="chip chip-functional" data-i18n="requirements_generator.summary.functional">Funcionales</span>
              <span id="functional-count">0</span>
            </div>
            <div class="distribution-item">
              <span class="chip chip-non-functional" data-i18n="requirements_generator.summary.non_functional">No Funcionales</span>
              <span id="non-functional-count">0</span>
            </div>
            <div class="distribution-item">
              <span class="chip chip-ambiguous" data-i18n="requirements_generator.summary.ambiguous">Ambiguos</span>
              <span id="ambiguous-count">0</span>
            </div>
            <div class="distribution-item">
              <span class="chip chip-non-ambiguous" data-i18n="requirements_generator.summary.non_ambiguous">No Ambiguos</span>
              <span id="non-ambiguous-count">0</span>
            </div>
          </div>
        </div>

        <div class="actions-container">
          <button id="btn-back-step2" class="btn btn-secondary">
            <i class="ri-arrow-left-line"></i> <span data-i18n="requirements_generator.buttons.back">Volver</span>
          </button>
          <button id="btn-save-requirements" class="btn btn-primary">
            <i class="ri-save-line"></i> <span data-i18n="requirements_generator.buttons.save">Guardar requisitos</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>