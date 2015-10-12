define(['knockout', 'jquery', 'jquery.ui', 'jquery.fracs', 'jquery.throttle-debounce'], function(ko, $, jqueryui, fracs, throttledebounce) {
    var HomePageViewModel = function() {

        ko.components.register('countdown', { require: 'components/countdown/countdown' });

        var self = this;
        // Holds the active statistic
        self.activeStatistic = ko.observable();

        // Update the hash in the URL when the active statistic changes
        self.activeStatistic.subscribe(function() {
            if (self.activeStatistic() != null) {
                window.location.hash = self.activeStatistic().split(' ').join('_');
            }
        });

        self.scrollToStatistic = function(statistic) {
            if (statistic) {
                $('html, body').animate({ scrollTop: statistic.offset().top }, 1000, "easeInOutQuad");
            }
        };

        self.goToNextStatistic = function(item, event) {
            var nextStat = $(event.target).parents('div[data-stat]').next();
            self.scrollToStatistic(nextStat);
        };

        self.goToPreviousStatistic = function(item, event) {
            var previousStat = $(event.target).parents('div[data-stat]').prev();
            self.scrollToStatistic(previousStat);
        };

        self.goToClickedStatistic = function(item, event) {
            event.preventDefault();
            var switchStat = $('div[data-stat="' + $(event.target).data('stat') + '"]');
            self.scrollToStatistic(switchStat);
        };

        self.changeSubstatistic = function(statistic, item, event) {
            var stat = $('div[data-stat="' + statistic + '"]')
            var substat = $(event.target).data('substat');

            // Slide in header from right
            $.when(
                stat.find('[data-substat]:not(nav [data-substat])').fadeOut(300).promise()
            ).done(function() {
                stat.find('[data-substat="' + substat + '"]:not(nav [data-substat])').fadeIn(300)
            });

        };

        $(window).on('scroll',
            $.debounce(100, function() {
                $('div[data-stat]').fracs('max', 'visible', function(best) {
                    self.activeStatistic($(best).data('stat'));
                });
            })
        );

        self.init = (function() {
            // Hide substatistics
            $('[data-substat]:not("li")').filter(function(index) {
                return $(this).data('substat') > 0;
            }).hide();

            // Grab the statistic from the hash in the URL
            if (window.location.hash != "" && window.location.hash != "#") {
                self.activeStatistic(window.location.hash.substring(1).split('_').join(' '));
                // scroll to statistic in window.location.hash
                self.scrollToStatistic($('div[data-stat="' + self.activeStatistic() + '"]'));
            }
        })();
    };

    return HomePageViewModel;
});