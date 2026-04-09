import numpy

x = numpy.linspace(0, 6*numpy.pi, 500, endpoint=False, dtype=float)

breathe = []

for i in x:
    breathe.append(int(round(20000*numpy.sin(i+((3*numpy.pi)/2)) + 45535)))
    
print(breathe)
