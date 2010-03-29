// Copyright 2009 Google Inc. All Rights Reserved.

/**
 * Defines a class that can render a simple Gantt chart.
 *
 * @author guido@google.com (Guido van Rossum)
 * @author schefflerjens@google.com (Jens Scheffler)
 */

function Gantt() {

  // Overridable configuration constants.
  this.PIX = 'stats/static/pix.gif';  // URL of a transparent 1x1 GIF.
  this.PREFIX = 'ae-stats-gantt-';  // Class name this.PREFIX.
  this.HEIGHT = '1em';  // Height of one bar.
  this.EXTRA_HEIGHT = '0.5em';  // Height of the extra bar.
  this.BG_COLOR = '#eeeeff';  // Background color for the bar.
  this.COLOR = '#7777ff';  // Color of the main bar.
  this.EXTRA_COLOR = '#ff6666';  // Color of the extra bar.
  this.INLINE_FONT_SIZE = '80%';  // Font size of inline_label.
  this.INLINE_TOP = '0.1em';  // Top of inline label text.
  this.TICK_COLOR = 'grey';  // Color for ticks.

  // Internal fields used to render the chart
  // Should not be modified
  var SCALES = [[5, 0.2, 1.0],
                [6, 0.2, 1.2],
                [5, 0.25, 1.25],
                [6, 0.25, 1.5],
                [4, 0.5, 2.0],
                [5, 0.5, 2.5],
                [6, 0.5, 3.0],
                [4, 1.0, 4.0],
                [5, 1.0, 5.0],
                [6, 1.0, 6.0],
                [4, 2.0, 8.0],
                [5, 2.0, 10.0]];
  var bars = [];
  var highest_duration = 0;
  var output = [];

  /*
   * Appends text to the output array
   */
  var write = function(text) {
    output.push(text);
  }

  /*
   * Internal helper to draw a table row showing the scale.
   */
  var draw_scale = function(gantt, howmany, spacing, scale) {
    write('<tr class="' + gantt.PREFIX + 'axisrow"><td width="20%"></td><td>');
    write('<div class="' + gantt.PREFIX + 'axis">');
    for (var i = 0; i <= howmany; i++) {
      write('<img class="' + gantt.PREFIX + 'tick" src="' +
            gantt.PIX + '" alt="" ');
      write('style="left:' + (i * spacing * scale) + '%"\n>');
      write('<span class="' + gantt.PREFIX + 'scale" style="left:' +
           (i * spacing * scale) + '%">');
      write('&nbsp;' + (i * spacing) + '</span>'); // TODO: number format %4g
    }
    write('</div></td></tr>\n');
  }

  /*
   * Helper to compute the proper X axis scale.
   * Args:
   *     highest: the highest value in the data series.
   *
   * Returns:
   *  A tuple (howmany, spacing, limit) where howmany is the number of
   *  increments, spacing is the increment to be used between successive
   *  axis labels, and limit is the rounded-up highest value of the
   *  axis.  Within float precision, howmany * spacing == highest will
   *  hold.
   *
   * The axis is assumed to always start at zero.
   */
  var compute_scale = function(highest) {
    if (highest <= 0) {
      return [2, 0.5, 1.0]  // Special-case if there's no data.
    }
    var scale = 1.0
    while (highest < 1.0) {
      highest *= 10.0
      scale /= 10.0
    }
    while (highest >= 10.0) {
      highest /= 10.0
      scale *= 10.0
    }
    // Now 1 <= highest < 10
    for (var i = 0; i < SCALES.length; i++) {
      if (highest <= SCALES[i][2]) {
        return [SCALES[i][0], SCALES[i][1] * scale, SCALES[i][2] * scale];
      }
    }
    // Avoid the need for "assert False".  Not actually reachable.
    return [5, 2.0 * scale, 10.0 * scale];
  }

  /*
   * Add a bar to the chart.
   * Args:
   *   label: Valid HTML or HTML-escaped text for the left column.
   *   start: Start time for the event.
   *   duration: Duration for the event.
   *   extra_duration: Duration for the second bar; use 0 to suppress.
   *   inline_label: Valid HTML or HTML-escaped text drawn after the bars;
   *       use '' to suppress.
   *   link_target: HTML-escaped link where clicking on any element
   *       will take you; use '' for no linking.
   * All arguments representing times or durations should be integers
   * or floats expressed in seconds.  The scale drawn is always
   * expressed in seconds (with limited precision).
   */
  this.add_bar = function(label, start, duration, extra_duration,
                          inline_label, link_target) {
    highest_duration = Math.max(
        highest_duration, Math.max(start + duration, start + extra_duration));
    bars.push({label: label, start: start, duration: duration,
               extra_duration: extra_duration, inline_label: inline_label,
               link_target: link_target});
    return this;
  };

  /*
   * Draw the bar chart as HTML.
   */
  this.draw = function() {
    output = [];
    var scale = compute_scale(highest_duration);
    var howmany = scale[0];
    var spacing = scale[1];
    var limit = scale[2];
    scale = 100.0 / limit;
    write('<table class="' + this.PREFIX + 'table">\n');
    draw_scale(this, howmany, spacing, scale);
    for (var i = 0; i < bars.length; i++) {
      var bar = bars[i];
      write('<tr class="' + this.PREFIX + 'datarow"><td width="20%">');
      if (bar.label.length > 0) {
        if (bar.link_target.length > 0) {
          write('<a class="' + this.PREFIX + 'link" href="' +
                bar.link_target + '">');
        }
        write(bar.label);
        if (bar.link_target.length > 0) {
          write('</a>');
        }
      }
      write('</td>\n<td>');
      write('<div class="' + this.PREFIX + 'container">');
      if (bar.link_target.length > 0) {
        write('<a class="' + this.PREFIX + 'link" href="' +
              bar.link_target + '"\n>');
      }
      write('<img class="' + this.PREFIX + 'bar" src="' +
            this.PIX + '" alt="" ');
      write('style="left:' + (bar.start * scale) + '%;width:' +
            (bar.duration * scale) + '%;min-width:1px"\n>');
      if (bar.extra_duration > 0) {
        write('<img class="' + this.PREFIX + 'extra" src="' +
              this.PIX + '" alt="" ');
        write('style="left:' + (bar.start * scale) + '%;width:' +
              (bar.extra_duration * scale) + '%"\n>');
      }
      if (bar.inline_label.length > 0) {
        write('<span class="' + this.PREFIX + 'inline" style="left:' +
              ((bar.start +
                Math.max(bar.duration, bar.extra_duration)) * scale) +
              '%">&nbsp;');
        write(bar.inline_label);
        write('</span>');
      }
      if (bar.link_target.length > 0) {
        write('</a>');
      }
      write('</div></td></tr>\n');

    }
    draw_scale(this, howmany, spacing, scale);
    write('</table>\n');
    return output.join('');
  };
}
