Kohana Objective Cookie Module
==============================

By default Kohana supports Cookie manipulation via it's static Kohana_Cookie
class. This makes it hard to work with cookies if your application requires to
support more than one, each with different settings. This module solves this
problem by giving you access to cookies served as objects, rather than static
methods, where each one is configurable on its own.
