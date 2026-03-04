//+------------------------------------------------------------------+
//|                                          Volume Combo Signal.mq5  |
//|                                   Copyright © 2025 - xAI Grok     |
//|                                                                  |
//| Description: Custom indicator for MetaTrader 5 that generates     |
//| buy and sell signals based on high volume and price action (local |
//| highs/lows). Signals are plotted as arrows for:                   |
//| - High volume buy (blue arrow): Volume >= 1.5x average volume at  |
//|   a local low, including the current candle.                     |
//| - High volume sell (red arrow): Volume >= 1.5x average volume at  |
//|   a local high, including the current candle.                    |
//| Other volume conditions are ignored.                             |
//|                                                                  |
//| Inputs:                                                          |
//| - TimeFrame: Chart timeframe in minutes (0 = current timeframe)   |
//| - LookbackPeriod: Bars to detect local highs/lows                |
//| - VolumeThreshold: Multiplier for high volume detection (e.g., 1.5)|
//| - VolumeLookback: Bars to calculate average volume               |
//| - EnableAlerts: Enable/disable alerts for new signals            |
//| - AlertPopup: Show popup alerts                                  |
//| - AlertSound: Play sound on alerts                               |
//| - DebugMode: Enable debug logging for signal details             |
//+------------------------------------------------------------------+
#property copyright "Copyright © 2025 - xAI Grok"
#property link      "https://x.ai"
#property version   "1.08"
#property indicator_chart_window
#property indicator_buffers 2
#property indicator_plots   2
#property strict

//---- indicator buffers
double BuySignalHigh[];        // High volume buy signal (blue arrow)
double SellSignalHigh[];       // High volume sell signal (red arrow)

//---- plot properties
#property indicator_type1   DRAW_ARROW
#property indicator_type2   DRAW_ARROW
#property indicator_width1  2 // Increased for visibility
#property indicator_width2  2 // Increased for visibility
#property indicator_color1  clrBlue
#property indicator_color2  clrRed
#property indicator_label1  "Buy High Volume"
#property indicator_label2  "Sell High Volume"

//---- indicator parameters
input int TimeFrame = 0;                // TimeFrame in min (0 = current)
input int LookbackPeriod = 21;           // Period for detecting highs/lows
input double VolumeThreshold = 1.0;     // Multiplier for high volume (e.g., 1.5)
input int VolumeLookback = 5;           // Period for average volume
input bool EnableAlerts = false;        // Enable alerts for signals
input bool AlertPopup = true;           // Show popup alerts
input bool AlertSound = true;           // Play sound on alerts
input bool DebugMode = true;            // Enable debug logging (default true for testing)

//+------------------------------------------------------------------+
//| Custom indicator initialization function                          |
//+------------------------------------------------------------------+
int OnInit()
{
   // Validate input parameters
   if (LookbackPeriod <= 0)
   {
      Print("Error: LookbackPeriod must be positive");
      return(INIT_PARAMETERS_INCORRECT);
   }
   if (VolumeLookback <= 0)
   {
      Print("Error: VolumeLookback must be positive");
      return(INIT_PARAMETERS_INCORRECT);
   }
   if (VolumeThreshold < 1.0)
   {
      Print("Error: VolumeThreshold must be at least 1.0");
      return(INIT_PARAMETERS_INCORRECT);
   }

   // Configure buy signal buffer
   SetIndexBuffer(0, BuySignalHigh);
   PlotIndexSetInteger(0, PLOT_ARROW, 233); // Up arrow
   PlotIndexSetInteger(0, PLOT_ARROW_SHIFT, 0);

   // Configure sell signal buffer
   SetIndexBuffer(1, SellSignalHigh);
   PlotIndexSetInteger(1, PLOT_ARROW, 234); // Down arrow
   PlotIndexSetInteger(1, PLOT_ARROW_SHIFT, 0);

   // Set buffers as time series
   ArraySetAsSeries(BuySignalHigh, true);
   ArraySetAsSeries(SellSignalHigh, true);

   // Set indicator name
   string short_name = MQLInfoString(MQL_PROGRAM_NAME) + "[" + tf(Period()) + "]";
   IndicatorSetString(INDICATOR_SHORTNAME, short_name);

   return(INIT_SUCCEEDED);
}

//+------------------------------------------------------------------+
//| Custom indicator deinitialization function                        |
//+------------------------------------------------------------------+
void OnDeinit(const int reason)
{
   // No cleanup needed
}

//+------------------------------------------------------------------+
//| Custom indicator calculation function                             |
//+------------------------------------------------------------------+
int OnCalculate(const int rates_total,
                const int prev_calculated,
                const datetime &time[],
                const double &open[],
                const double &high[],
                const double &low[],
                const double &close[],
                const long &tick_volume[],
                const long &volume[],
                const int &spread[])
{
   ArraySetAsSeries(time, true);
   ArraySetAsSeries(open, true);
   ArraySetAsSeries(high, true);
   ArraySetAsSeries(low, true);
   ArraySetAsSeries(close, true);
   ArraySetAsSeries(tick_volume, true);

   // Check if enough bars are available
   if (rates_total < LookbackPeriod + VolumeLookback)
   {
      Print("Error: Insufficient bars (", rates_total, ") for LookbackPeriod (", LookbackPeriod, ") and VolumeLookback (", VolumeLookback, ")");
      return(0);
   }

   // Determine calculation limit
   int limit = MathMin(rates_total - prev_calculated, LookbackPeriod + 1);
   if (prev_calculated == 0) limit = rates_total - LookbackPeriod;

   // Initialize buffers on first call
   if (prev_calculated < 1)
   {
      ArrayInitialize(BuySignalHigh, EMPTY_VALUE);
      ArrayInitialize(SellSignalHigh, EMPTY_VALUE);
   }

   if (limit < 2)
      limit = 2;

   for (int shift = limit; shift >= 0; shift--) // Changed to include shift = 0
   {
      // Calculate average volume
      double avgVolume = 0.0;
      int count = 0;
      for (int i = 1; i <= VolumeLookback && (shift + i) < rates_total; i++)
      {
         avgVolume += (double)tick_volume[shift + i];
         count++;
      }
      if (count > 0 && avgVolume > 0)
         avgVolume /= count;
      else
         avgVolume = 1.0; // Avoid division by zero

      double currentVolume = (double)tick_volume[shift];

      // Identify buy candle (local low)
      double minLow = low[shift];
      bool validLow = true;
      for (int i = 1; i <= LookbackPeriod && (shift + i) < rates_total && (shift - i) >= 0; i++)
      {
         minLow = MathMin(minLow, low[shift + i]);
         if (shift - i >= 0)
            minLow = MathMin(minLow, low[shift - i]);
      }
      bool isBuyCandle = (low[shift] <= minLow);

      // Identify sell candle (local high)
      double maxHigh = high[shift];
      bool validHigh = true;
      for (int i = 1; i <= LookbackPeriod && (shift + i) < rates_total && (shift - i) >= 0; i++)
      {
         maxHigh = MathMax(maxHigh, high[shift + i]);
         if (shift - i >= 0)
            maxHigh = MathMax(maxHigh, high[shift - i]);
      }
      bool isSellCandle = (high[shift] >= maxHigh);

      // Plot buy signal (high volume only)
      BuySignalHigh[shift] = EMPTY_VALUE;
      if (isBuyCandle && currentVolume >= avgVolume * VolumeThreshold)
      {
         BuySignalHigh[shift] = low[shift];
         if (EnableAlerts)
         {
            string message = "Buy High Volume Signal at " + TimeToString(time[shift]) + " Price: " + DoubleToString(low[shift], _Digits);
            if (AlertPopup) Alert(message);
            if (AlertSound) PlaySound("alert.wav");
         }
      }

      // Plot sell signal (high volume only)
      SellSignalHigh[shift] = EMPTY_VALUE;
      if (isSellCandle && currentVolume >= avgVolume * VolumeThreshold)
      {
         SellSignalHigh[shift] = high[shift];
         if (EnableAlerts)
         {
            string message = "Sell High Volume Signal at " + TimeToString(time[shift]) + " Price: " + DoubleToString(high[shift], _Digits);
            if (AlertPopup) Alert(message);
            if (AlertSound) PlaySound("alert.wav");
         }
      }

      // Debugging
      if (DebugMode)
      {
         Print("Shift: ", shift, " Time: ", TimeToString(time[shift]),
               " Volume: ", currentVolume, " AvgVolume: ", avgVolume,
               " Threshold: ", VolumeThreshold, " IsBuyCandle: ", isBuyCandle,
               " IsSellCandle: ", isSellCandle,
               " BuySignal: ", BuySignalHigh[shift] != EMPTY_VALUE ? "Yes" : "No",
               " SellSignal: ", SellSignalHigh[shift] != EMPTY_VALUE ? "Yes" : "No");
      }
   }

   return(rates_total);
}

//+------------------------------------------------------------------+
//| Timeframe conversion function                                    |
//+------------------------------------------------------------------+
string tf(int timeframe)
{
   switch(timeframe)
   {
      case PERIOD_M1:  return("M1");
      case PERIOD_M2:  return("M2");
      case PERIOD_M3:  return("M3");
      case PERIOD_M4:  return("M4");
      case PERIOD_M5:  return("M5");
      case PERIOD_M6:  return("M6");
      case PERIOD_M10: return("M10");
      case PERIOD_M12: return("M12");
      case PERIOD_M15: return("M15");
      case PERIOD_M20: return("M20");
      case PERIOD_M30: return("M30");
      case PERIOD_H1:  return("H1");
      case PERIOD_H2:  return("H2");
      case PERIOD_H3:  return("H3");
      case PERIOD_H4:  return("H4");
      case PERIOD_H6:  return("H6");
      case PERIOD_H8:  return("H8");
      case PERIOD_H12: return("H12");
      case PERIOD_D1:  return("D1");
      case PERIOD_W1:  return("W1");
      case PERIOD_MN1: return("MN1");
      default:         return("Unknown timeframe");
   }
}
