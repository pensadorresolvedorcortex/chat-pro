enum NotificationPresentationOption {
  alert('alert'),
  sound('sound'),
  badge('badge'),
  banner('banner'),
  list('list'),
  announcement('announcement'),
  timeSensitive('timeSensitive'),
  criticalAlert('criticalAlert');

  const NotificationPresentationOption(this._wireName);

  final String _wireName;

  String get wireName => _wireName;

  static Set<NotificationPresentationOption> fromDynamicList(dynamic raw) {
    if (raw is Iterable) {
      return raw
          .whereType<String>()
          .map(_normalize)
          .map(_fromWireName)
          .whereType<NotificationPresentationOption>()
          .toSet();
    }

    return const <NotificationPresentationOption>{};
  }

  static List<String> toWireList(Iterable<NotificationPresentationOption> options) {
    return options.map((option) => option.wireName).toList(growable: false);
  }

  static String _normalize(String value) => value.trim().toLowerCase();

  static NotificationPresentationOption? _fromWireName(String value) {
    for (final option in NotificationPresentationOption.values) {
      if (option.wireName == value) {
        return option;
      }
    }

    return null;
  }
}

