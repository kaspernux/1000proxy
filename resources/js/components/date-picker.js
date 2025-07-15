/**
 * Advanced Date/Time Picker Component
 * Supports various formats, timezones, and validation
 */
export default () => ( {
    // Component State
    isOpen: false,
    selectedDate: null,
    selectedTime: null,
    viewDate: new Date(),

    // Configuration
    mode: 'date', // date, time, datetime, range
    format: 'YYYY-MM-DD',
    timeFormat: 'HH:mm',
    timezone: 'local',
    minDate: null,
    maxDate: null,
    disabledDates: [],

    // Display Options
    showWeekNumbers: false,
    firstDayOfWeek: 0, // 0 = Sunday, 1 = Monday
    showToday: true,
    showClear: true,
    showTime: false,
    use24Hour: true,

    // Localization
    locale: 'en',
    months: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ],
    monthsShort: [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ],
    weekdays: [
        'Sunday', 'Monday', 'Tuesday', 'Wednesday',
        'Thursday', 'Friday', 'Saturday'
    ],
    weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],

    // Range Selection
    rangeStart: null,
    rangeEnd: null,
    hoverDate: null,

    // Lifecycle
    init ()
    {
        if ( this.mode.includes( 'time' ) )
        {
            this.showTime = true;
        }

        this.setupEventListeners();
        this.$watch( 'selectedDate', () => this.handleDateChange() );
        this.$watch( 'selectedTime', () => this.handleTimeChange() );
    },

    // Event Handlers
    toggle ()
    {
        this.isOpen = !this.isOpen;
        if ( this.isOpen )
        {
            this.viewDate = this.selectedDate ? new Date( this.selectedDate ) : new Date();
        }
    },

    selectDate ( date )
    {
        if ( this.isDateDisabled( date ) ) return;

        if ( this.mode === 'range' )
        {
            this.handleRangeSelection( date );
        } else
        {
            this.selectedDate = new Date( date );
            if ( this.mode === 'date' )
            {
                this.close();
            }
        }
    },

    selectTime ( hour, minute )
    {
        const time = `${ hour.toString().padStart( 2, '0' ) }:${ minute.toString().padStart( 2, '0' ) }`;
        this.selectedTime = time;
    },

    handleRangeSelection ( date )
    {
        if ( !this.rangeStart || ( this.rangeStart && this.rangeEnd ) )
        {
            // Start new range
            this.rangeStart = new Date( date );
            this.rangeEnd = null;
        } else if ( this.rangeStart && !this.rangeEnd )
        {
            // Complete range
            if ( date < this.rangeStart )
            {
                this.rangeEnd = this.rangeStart;
                this.rangeStart = new Date( date );
            } else
            {
                this.rangeEnd = new Date( date );
            }
            this.close();
        }
    },

    close ()
    {
        this.isOpen = false;
        this.hoverDate = null;
    },

    clear ()
    {
        this.selectedDate = null;
        this.selectedTime = null;
        this.rangeStart = null;
        this.rangeEnd = null;
        this.$dispatch( 'date-cleared' );
    },

    today ()
    {
        const today = new Date();
        this.selectDate( today );
        this.viewDate = today;
    },

    // Navigation
    previousMonth ()
    {
        this.viewDate = new Date( this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1 );
    },

    nextMonth ()
    {
        this.viewDate = new Date( this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1 );
    },

    previousYear ()
    {
        this.viewDate = new Date( this.viewDate.getFullYear() - 1, this.viewDate.getMonth(), 1 );
    },

    nextYear ()
    {
        this.viewDate = new Date( this.viewDate.getFullYear() + 1, this.viewDate.getMonth(), 1 );
    },

    // Calendar Generation
    getCalendarDays ()
    {
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();
        const firstDay = new Date( year, month, 1 );
        const lastDay = new Date( year, month + 1, 0 );

        // Calculate start date (including previous month days)
        const startDate = new Date( firstDay );
        startDate.setDate( startDate.getDate() - ( ( firstDay.getDay() - this.firstDayOfWeek + 7 ) % 7 ) );

        const days = [];
        const current = new Date( startDate );

        // Generate 42 days (6 weeks)
        for ( let i = 0; i < 42; i++ )
        {
            days.push( {
                date: new Date( current ),
                day: current.getDate(),
                isCurrentMonth: current.getMonth() === month,
                isToday: this.isToday( current ),
                isSelected: this.isSelected( current ),
                isDisabled: this.isDateDisabled( current ),
                isInRange: this.isInRange( current ),
                isRangeStart: this.isRangeStart( current ),
                isRangeEnd: this.isRangeEnd( current ),
                isHovered: this.isHovered( current )
            } );

            current.setDate( current.getDate() + 1 );
        }

        return days;
    },

    getWeekNumbers ()
    {
        const days = this.getCalendarDays();
        const weeks = [];

        for ( let i = 0; i < days.length; i += 7 )
        {
            const weekStart = days[ i ].date;
            weeks.push( this.getWeekNumber( weekStart ) );
        }

        return weeks;
    },

    getWeekNumber ( date )
    {
        const target = new Date( date.valueOf() );
        const dayNr = ( date.getDay() + 6 ) % 7;
        target.setDate( target.getDate() - dayNr + 3 );
        const firstThursday = target.valueOf();
        target.setMonth( 0, 1 );
        if ( target.getDay() !== 4 )
        {
            target.setMonth( 0, 1 + ( ( 4 - target.getDay() ) + 7 ) % 7 );
        }
        return 1 + Math.ceil( ( firstThursday - target ) / 604800000 );
    },

    // Time Generation
    getHours ()
    {
        const hours = [];
        const max = this.use24Hour ? 24 : 12;

        for ( let i = 0; i < max; i++ )
        {
            const hour = this.use24Hour ? i : ( i === 0 ? 12 : i );
            hours.push( {
                value: i,
                display: hour.toString().padStart( 2, '0' ),
                period: this.use24Hour ? '' : ( i < 12 ? 'AM' : 'PM' )
            } );
        }

        return hours;
    },

    getMinutes ()
    {
        const minutes = [];
        for ( let i = 0; i < 60; i += 5 )
        {
            minutes.push( {
                value: i,
                display: i.toString().padStart( 2, '0' )
            } );
        }
        return minutes;
    },

    // Validation
    isDateDisabled ( date )
    {
        if ( this.minDate && date < this.minDate ) return true;
        if ( this.maxDate && date > this.maxDate ) return true;

        return this.disabledDates.some( disabled =>
        {
            if ( disabled instanceof Date )
            {
                return this.isSameDay( date, disabled );
            }
            if ( typeof disabled === 'function' )
            {
                return disabled( date );
            }
            return false;
        } );
    },

    // Date Comparison Utilities
    isToday ( date )
    {
        return this.isSameDay( date, new Date() );
    },

    isSelected ( date )
    {
        if ( this.mode === 'range' )
        {
            return this.isRangeStart( date ) || this.isRangeEnd( date );
        }
        return this.selectedDate && this.isSameDay( date, this.selectedDate );
    },

    isSameDay ( date1, date2 )
    {
        return date1.getFullYear() === date2.getFullYear() &&
            date1.getMonth() === date2.getMonth() &&
            date1.getDate() === date2.getDate();
    },

    isInRange ( date )
    {
        if ( this.mode !== 'range' || !this.rangeStart ) return false;

        if ( this.rangeEnd )
        {
            return date >= this.rangeStart && date <= this.rangeEnd;
        }

        if ( this.hoverDate )
        {
            const start = this.rangeStart < this.hoverDate ? this.rangeStart : this.hoverDate;
            const end = this.rangeStart < this.hoverDate ? this.hoverDate : this.rangeStart;
            return date >= start && date <= end;
        }

        return false;
    },

    isRangeStart ( date )
    {
        return this.rangeStart && this.isSameDay( date, this.rangeStart );
    },

    isRangeEnd ( date )
    {
        return this.rangeEnd && this.isSameDay( date, this.rangeEnd );
    },

    isHovered ( date )
    {
        return this.hoverDate && this.isSameDay( date, this.hoverDate );
    },

    // Formatting
    formatDate ( date, format = this.format )
    {
        if ( !date ) return '';

        const year = date.getFullYear();
        const month = ( date.getMonth() + 1 ).toString().padStart( 2, '0' );
        const day = date.getDate().toString().padStart( 2, '0' );

        return format
            .replace( 'YYYY', year )
            .replace( 'MM', month )
            .replace( 'DD', day )
            .replace( 'M', date.getMonth() + 1 )
            .replace( 'D', date.getDate() );
    },

    formatTime ( time, format = this.timeFormat )
    {
        if ( !time ) return '';

        const [ hours, minutes ] = time.split( ':' );

        if ( this.use24Hour )
        {
            return format
                .replace( 'HH', hours.padStart( 2, '0' ) )
                .replace( 'mm', minutes.padStart( 2, '0' ) );
        } else
        {
            const hour12 = parseInt( hours ) % 12 || 12;
            const period = parseInt( hours ) < 12 ? 'AM' : 'PM';

            return format
                .replace( 'hh', hour12.toString().padStart( 2, '0' ) )
                .replace( 'mm', minutes.padStart( 2, '0' ) )
                .replace( 'A', period );
        }
    },

    getDisplayValue ()
    {
        if ( this.mode === 'range' )
        {
            if ( this.rangeStart && this.rangeEnd )
            {
                return `${ this.formatDate( this.rangeStart ) } - ${ this.formatDate( this.rangeEnd ) }`;
            }
            if ( this.rangeStart )
            {
                return this.formatDate( this.rangeStart );
            }
            return '';
        }

        let value = this.selectedDate ? this.formatDate( this.selectedDate ) : '';

        if ( this.showTime && this.selectedTime )
        {
            value += value ? ` ${ this.formatTime( this.selectedTime ) }` : this.formatTime( this.selectedTime );
        }

        return value;
    },

    // Event Handlers
    handleDateChange ()
    {
        this.$dispatch( 'date-changed', {
            date: this.selectedDate,
            formatted: this.formatDate( this.selectedDate )
        } );
    },

    handleTimeChange ()
    {
        this.$dispatch( 'time-changed', {
            time: this.selectedTime,
            formatted: this.formatTime( this.selectedTime )
        } );
    },

    // Event Listeners
    setupEventListeners ()
    {
        document.addEventListener( 'click', ( e ) =>
        {
            if ( !this.$el.contains( e.target ) )
            {
                this.close();
            }
        } );
    },

    // Style Getters
    getDayClasses ( day )
    {
        const baseClasses = [
            'w-8 h-8 flex items-center justify-center text-sm cursor-pointer rounded',
            'hover:bg-blue-100 transition-colors duration-150'
        ];

        const stateClasses = [];

        if ( !day.isCurrentMonth )
        {
            stateClasses.push( 'text-gray-400' );
        }

        if ( day.isToday )
        {
            stateClasses.push( 'bg-blue-100', 'font-semibold' );
        }

        if ( day.isSelected )
        {
            stateClasses.push( 'bg-blue-600', 'text-white', 'hover:bg-blue-700' );
        }

        if ( day.isDisabled )
        {
            stateClasses.push( 'text-gray-300', 'cursor-not-allowed', 'hover:bg-transparent' );
        }

        if ( day.isInRange && !day.isSelected )
        {
            stateClasses.push( 'bg-blue-200' );
        }

        return [ ...baseClasses, ...stateClasses ].join( ' ' );
    }
} );
