/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import BaseDashletView from 'views/dashlets/abstract/base';
import Espo from 'espo';

class ConversationSummarizerDashletView extends BaseDashletView {

    name = 'ConversationSummarizer'

    events = {
        'click button[data-action="summarize"]': function () {
            this.handleSummarize();
        },
        'click button[data-action="clear"]': function () {
            this.summaryData = null;
            this.errorMessage = null;
            this.reRender();
        }
    }

    templateContent = `
        <div class="conversation-summarizer-container" style="padding: 12px;">
            <div class="row" style="margin-bottom: 12px;">
                <div class="col-sm-12">
                    <label class="control-label" style="font-weight: 600;">Conversation / Meeting Notes</label>
                    <textarea class="form-control summarizer-input" rows="4" placeholder="Paste conversation transcript, customer call notes, or meeting discussion here..." style="resize: vertical; font-family: inherit;">{{inputText}}</textarea>
                </div>
            </div>
            <div class="row" style="margin-bottom: 14px;">
                <div class="col-sm-8">
                    <span class="text-muted small">
                        <i class="fas fa-brain"></i> Powered by Groq LLM ({{model}})
                    </span>
                </div>
                <div class="col-sm-4 text-right">
                    <button class="btn btn-default btn-sm" data-action="clear" style="margin-right: 6px;">Clear</button>
                    <button class="btn btn-primary btn-sm" data-action="summarize" {{#if isSubmitting}}disabled{{/if}}>
                        {{#if isSubmitting}}
                            <i class="fas fa-spinner fa-spin"></i> Summarizing...
                        {{else}}
                            <i class="fas fa-wand-magic-sparkles"></i> Summarize
                        {{/if}}
                    </button>
                </div>
            </div>

            {{#if errorMessage}}
            <div class="alert alert-danger" style="margin-top: 10px; padding: 10px;">
                <i class="fas fa-exclamation-triangle"></i> {{errorMessage}}
            </div>
            {{/if}}

            {{#if summaryData}}
            <div class="summary-result-card" style="margin-top: 14px; padding: 14px; background: rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.08); border-radius: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h5 style="margin: 0; font-weight: 700; color: #2c3e50;">
                        <i class="fas fa-file-alt text-primary"></i> Executive Summary
                    </h5>
                    <div>
                        <span class="badge" style="background-color: {{sentimentBadgeColor}}; margin-right: 4px;">{{summaryData.sentiment}}</span>
                        <span class="badge" style="background-color: {{tempBadgeColor}};">{{summaryData.dealTemperature}}</span>
                    </div>
                </div>

                <p style="margin-bottom: 12px; line-height: 1.5; color: #34495e;">{{summaryData.summary}}</p>

                {{#if summaryData.keyPoints.length}}
                <div style="margin-bottom: 10px;">
                    <strong style="color: #2c3e50; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Key Discussion Points</strong>
                    <ul style="margin: 6px 0 0 0; padding-left: 20px;">
                        {{#each summaryData.keyPoints}}
                        <li style="margin-bottom: 4px; color: #495057;">{{this}}</li>
                        {{/each}}
                    </ul>
                </div>
                {{/if}}

                {{#if summaryData.actionItems.length}}
                <div style="margin-bottom: 10px;">
                    <strong style="color: #2c3e50; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Action Items &amp; Next Steps</strong>
                    <ul style="margin: 6px 0 0 0; padding-left: 20px;">
                        {{#each summaryData.actionItems}}
                        <li style="margin-bottom: 4px; color: #495057;"><i class="far fa-check-circle text-success"></i> {{this}}</li>
                        {{/each}}
                    </ul>
                </div>
                {{/if}}

                <div class="text-muted small" style="margin-top: 10px; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 8px; display: flex; justify-content: space-between;">
                    <span>Model: {{summaryData.model}}</span>
                    <span>Tokens: {{summaryData.usage.totalTokens}}</span>
                </div>
            </div>
            {{/if}}
        </div>
    `

    data() {
        var sentiment = (this.summaryData && this.summaryData.sentiment) || 'Neutral';
        var sentimentColor = sentiment === 'Positive' ? '#27ae60' : (sentiment === 'Negative' ? '#e74c3c' : '#7f8c8d');

        var temp = (this.summaryData && this.summaryData.dealTemperature) || 'Warm';
        var tempColor = temp === 'Hot' ? '#e67e22' : (temp === 'Cold' ? '#3498db' : '#f39c12');

        return {
            title: this.getOption('title') || 'AI Conversation Summarizer',
            model: this.getOption('model') || 'llama-3.3-70b-versatile',
            inputText: this.inputText || '',
            isSubmitting: this.isSubmitting || false,
            errorMessage: this.errorMessage || null,
            summaryData: this.summaryData || null,
            sentimentBadgeColor: sentimentColor,
            tempBadgeColor: tempColor,
        };
    }

    handleSummarize() {
        var text = (this.$el.find('textarea.summarizer-input').val() || '').trim();
        if (!text) {
            this.errorMessage = 'Please enter conversation text or discussion notes to summarize.';
            this.reRender();
            return;
        }

        this.inputText = text;
        this.isSubmitting = true;
        this.errorMessage = null;
        this.reRender();

        var self = this;
        Espo.Ajax.postRequest('ConversationSummarizer/summarize', {
            text: text,
            model: this.getOption('model') || 'llama-3.3-70b-versatile',
            simulate: true // graceful fallback if key not configured
        }).then(function (response) {
            self.isSubmitting = false;
            if (response && response.data) {
                self.summaryData = response.data;
            } else {
                self.errorMessage = 'Received empty response from AI service.';
            }
            self.reRender();
        }).catch(function (xhr) {
            self.isSubmitting = false;
            var msg = 'Failed to generate summary.';
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            self.errorMessage = msg;
            self.reRender();
        });
    }
}

export default ConversationSummarizerDashletView;
