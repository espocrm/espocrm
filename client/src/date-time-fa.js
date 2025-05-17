/** @module date-time-fa */

import DateTime from 'date-time';
import moment from 'moment';

/**
 * Extended DateTime util with Farsi/Georgian calendar handling
 */
class DateTimeFa extends DateTime {
    /**
     * @inheritdoc
     */
    setLanguage(language) {
        super.setLanguage(language);

        // For Farsi language, force Georgian calendar display while keeping internal format
        if (language.get('language') === 'fa') {
            moment.locale('en'); // Force English (Georgian) locale for dates
            
            // Override date format methods for Farsi
            this.origToDisplayDate = this.toDisplayDate;
            this.origFromDisplayDate = this.fromDisplayDate;
            
            this.toDisplayDate = (string) => {
                if (!string || (typeof string !== 'string')) {
                    return '';
                }

                const m = moment(string, this.internalDateFormat);

                if (!m.isValid()) {
                    return '';
                }

                // Always format in Georgian calendar
                return m.format(this.dateFormat);
            };

            this.fromDisplayDate = (string) => {
                const m = moment(string, this.dateFormat);

                if (!m.isValid()) {
                    return -1;
                }

                // Store in internal format
                return m.format(this.internalDateFormat);
            };
        }
    }
}

export default DateTimeFa;