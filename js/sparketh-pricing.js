/*
 *  Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

(function ($) {
    $(document).ready( function() {
        var e20rSponsoredPricing = {
            init: function () {
                this.current_price = parseFloat(sparketh.initial_price);
                this.current_seats = parseInt(sparketh.current_seats);
                this.account_price = parseInt(sparketh.level_cost);
                this.per_seat_price = parseFloat(sparketh.per_seat_price);
                this.min_seats = parseInt( sparketh.initial_seats );

                var self = this;

                $('#seats').change(function () {
                    window.console.log("Updating seat count to...");
                    self.onUpdateSeats();
                });
            },
            calculatePrice: function () {
                var self = this;
                var seats = parseInt( $('#seats').val() );

                if ( self.min_seats > seats ) {
                    window.alert('Cannot specify less than ' + self.min_seats + " extra accounts/seats" );
                    return;
                }

                self.current_seats = seats;
                self.current_price = parseFloat((self.account_price + (self.current_seats * self.per_seat_price)));
            },
            onUpdateSeats: function () {

                var self = this;
                self.calculatePrice();
                $('span.e20r-full-price').text(self.current_price.toFixed(2));
                $('span.e20r-child-accounts').text(self.current_seats);
            }
        }

        e20rSponsoredPricing.init();
    });
})(jQuery);

