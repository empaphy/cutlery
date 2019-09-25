# Cutlery

A forking library for PHP.


## Known issues:

### Fork:

  - Objects in result that aren't clonable might be modified to make them serializable.

### Synchronizer:

  - Methods are performed on both sides. This might cause weird effects if they depend on changes outside their scope.
  - Yields are unsupported for now


## Todo:

  - Perform method calls on only one side (parent by default) and sync result to the other